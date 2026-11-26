<?php

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * REPOSITORY da batalha: monta uma batalha nova (escolhe os oponentes) e,
 * quando ela acaba, grava tudo nas tabelas de LOG (batalhas e batalha_turnos)
 * numa transação. Também lê o histórico pela view vw_historico_batalhas.
 */
class BatalhaRepository
{
    /**
     * DIFICULDADE dos treinadores comuns: a força buscada para os inimigos é a
     * média do seu time x 1.10 (10% mais fortes). 1.00 = mesma força.
     */
    public const DIFICULDADE = 1.10;

    /** Inimigos podem ter até 15% de força a mais ou a menos que essa força buscada. */
    public const FOLGA_DE_FORCA = 0.15;


    /** Nomes dos treinadores do computador (sorteado a cada batalha). */
    private const TREINADORES = [
        'Treinador Lucas',
        'Treinadora Bia',
        'Campista Rui',
        'Pescador Tião',
        'Escoteira Ana',
        'Montanhista Leo',
    ];

    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    /**
     * Monta uma batalha nova.
     * Treinador comum ($lider = null): BALANCEADO pelo time do jogador:
     *   - o computador tem de 1 até a quantidade de Pokémon de pé do jogador;
     *   - cada inimigo tem força parecida com a MÉDIA de força do time do jogador,
     *     um pouco acima dela (DIFICULDADE).
     * Líder de ginásio: time FIXO (tabela lider_pokemons), com o HP multiplicado
     * pela dificuldade do líder (coluna bonus_hp); o jogador luta com a mesma
     * quantidade de Pokémon que o líder.
     * Lendário ($lendario): só ele do outro lado, com o HP x LendarioRepository::BONUS_HP,
     * contra o time inteiro do jogador. $lendarioBase = quem é desbloqueado se
     * o jogador vencer (a base da linha: Kubfu na batalha contra o Urshifu).
     */
    public function nova(
        int $usuarioId,
        ?Lider $lider = null,
        ?Especie $lendario = null,
        ?Especie $lendarioBase = null
    ): Batalha {
        $golpes = new GolpeRepository($this->pdo);

        $time = (new TimeRepository($this->pdo))->carregar($usuarioId)->getPokemons();
        if (!$time) {
            throw new \InvalidArgumentException('Seu time está vazio. Monte um time no Team Builder.');
        }
        $dePe = array_values(array_filter($time, fn(Pokemon $p) => !$p->estaDesmaiado()));
        if (!$dePe) {
            throw new \InvalidArgumentException(
                'Nenhum Pokémon do seu time está de pé. Cure o time de graça no Centro Pokémon (Team Builder).'
            );
        }

        // Regra do ginásio: contra um líder com N Pokémon, o jogador também só
        // usa N: os N primeiros Pokémon DE PÉ do time, na ordem dos slots.
        // Os outros ficam fora da batalha (e não perdem HP).
        if ($lider !== null) {
            $time = array_slice($dePe, 0, $lider->getQuantidade());
        }
        $golpesTime = array_map(fn(Pokemon $p) => $golpes->daEspecie($p->getEspecie()->getId()), $time);
        $especies = new EspecieRepository($this->pdo);
        if ($lendario !== null) {
            $sorteadas = [$lendario]; // batalha lendária: o lendário sozinho contra o time todo
        } elseif ($lider !== null) {
            $sorteadas = $especies->doLider($lider->getId());
            if (!$sorteadas) {
                throw new \InvalidArgumentException('Esse líder ainda não tem time cadastrado no banco.');
            }
        } else {
            $media = intdiv(array_sum(array_map(fn(Pokemon $p) => $p->getEspecie()->forca(), $dePe)), count($dePe));
            $alvo = (int) round($media * self::DIFICULDADE);
            $sorteadas = $especies->sortearParecidas(
                random_int(1, count($dePe)),
                $alvo,
                (int) round($alvo * self::FOLGA_DE_FORCA)
            );
        }

        // usuário 0 = computador; Pokémon novo nasce com o HP cheio
        $oponente = array_map(
            fn(Especie $e) => match (true) {
                $lendario !== null => $this->pokemonComHpExtra($e, LendarioRepository::BONUS_HP),
                $lider !== null => $this->pokemonComHpExtra($e, $lider->getBonusHp()),
                default => Pokemon::novoDaEspecie(0, $e),
            },
            $sorteadas
        );
        $golpesOponente = array_map(fn(Pokemon $p) => $golpes->daEspecie($p->getEspecie()->getId()), $oponente);

        return new Batalha(
            $time,
            $golpesTime,
            $oponente,
            $golpesOponente,
            $lendario !== null
            ? 'Lendário ' . $lendario->getNome()
            : ($lider?->getNome() ?? self::TREINADORES[array_rand(self::TREINADORES)]),
            $lider,
            $lendario,
            $lendarioBase
        );
    }

    /** Pokémon mais forte (líder ou lendário): HP máximo = HP da espécie x bônus. */
    private function pokemonComHpExtra(Especie $e, float $bonusHp): Pokemon
    {
        $hp = (int) round($e->getHp() * $bonusHp);
        return new Pokemon(null, 0, $e, null, 5, $hp, $hp);
    }

    /**
     * Grava a batalha que acabou. TRANSAÇÃO (DTL): ou grava tudo, ou nada.
     *   1) batalhas         -> o trigger trg_batalhas_premio paga o prêmio
     *                          (lider_id diz se foi contra um líder de ginásio);
     *                          o trigger trg_batalhas_lendario desbloqueia o lendário vencido
     *                          (lendario_id diz se foi uma batalha lendária);
     *   2) batalha_turnos   -> o log de cada ação;
     *   3) pokemons         -> o HP e a condição com que o time saiu da batalha.
     * Devolve o id da batalha gravada.
     */
    public function salvar(int $usuarioId, Batalha $b): int
    {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare(
                'INSERT INTO batalhas (usuario_id, lider_id, lendario_id, oponente_nome, vencedor, dinheiro_ganho, total_turnos)
                 VALUES (:u, :lider, :lendario, :oponente, :vencedor, :dinheiro, :turnos)'
            )->execute([
                        'u' => $usuarioId,
                        'lider' => $b->getLider()?->getId(), // NULL = treinador comum
                        'lendario' => $b->getLendario()?->getId(), // vitória: o trigger trg_batalhas_lendario desbloqueia
                        'oponente' => $b->getOponenteNome(),
                        'vencedor' => $b->getVencedor(),
                        'dinheiro' => $b->premio(),
                        'turnos' => $b->getNumero(),
                    ]);
            $batalhaId = (int) $this->pdo->lastInsertId();

            $st = $this->pdo->prepare(
                'INSERT INTO batalha_turnos (batalha_id, turno, ator, acao, dano, hp_restante)
                 VALUES (:b, :turno, :ator, :acao, :dano, :hp)'
            );
            foreach ($b->getTurnos() as $t) {
                $st->execute([
                    'b' => $batalhaId,
                    'turno' => $t->getNumero(),
                    'ator' => $t->getAtor(),
                    'acao' => $t->getAcao(),
                    'dano' => $t->getDano(),
                    'hp' => $t->getHpRestante(),
                ]);
            }

            // desmaiado e status de quem ficou com 0 HP: o trigger trg_pokemons_hp_update ajusta
            $st = $this->pdo->prepare(
                'UPDATE pokemons SET hp_atual = :hp, status = :status WHERE id = :id AND usuario_id = :u'
            );
            foreach ($b->getJogador() as $p) {
                $st->execute([
                    'hp' => $p->getHpAtual(),
                    'status' => $p->getStatus(),
                    'id' => $p->getId(),
                    'u' => $usuarioId,
                ]);
            }

            $this->pdo->commit();
            return $batalhaId;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /** Últimas 10 batalhas do jogador, lidas da VIEW vw_historico_batalhas (DQL). */
    public function historico(int $usuarioId): array
    {
        $st = $this->pdo->prepare(
            'SELECT oponente_nome, vencedor, dinheiro_ganho, total_turnos, data_batalha
               FROM vw_historico_batalhas
              WHERE usuario_id = :u
              ORDER BY data_batalha DESC, id DESC
              LIMIT 10'
        );
        $st->execute(['u' => $usuarioId]);
        return $st->fetchAll();
    }
}
