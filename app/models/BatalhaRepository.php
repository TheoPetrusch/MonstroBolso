<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class BatalhaRepository
{
    /** Inimigos podem ter até 15% de força a mais ou a menos que a média do seu time. */
    public const FOLGA_DE_FORCA = 0.15;

    /** Pokémon do líder: até 10% de diferença da força do líder. */
    public const FOLGA_DO_LIDER = 0.10;

    /** Nomes dos treinadores do computador (sorteado a cada batalha). */
    private const TREINADORES = [
        'Treinador Lucas', 'Treinadora Bia', 'Campista Rui',
        'Pescador Tião', 'Escoteira Ana', 'Montanhista Leo',
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
     *   - cada inimigo tem força parecida com a MÉDIA de força do time do jogador.
     * Líder de ginásio: força e quantidade FIXAS do líder (ficam mais fortes a
     * cada ginásio), com Pokémon do tipo em que ele é especialista.
     */
    public function nova(int $usuarioId, ?Lider $lider = null): Batalha
    {
        $golpes = new GolpeRepository($this->pdo);

        $time = (new TimeRepository($this->pdo))->carregar($usuarioId)->getPokemons();
        if (!$time) {
            throw new \InvalidArgumentException('Seu time está vazio. Monte um time no Team Builder.');
        }
        $golpesTime = array_map(fn(Pokemon $p) => $golpes->daEspecie($p->getEspecie()->getId()), $time);

        $dePe = array_filter($time, fn(Pokemon $p) => !$p->estaDesmaiado());
        if (!$dePe) {
            throw new \InvalidArgumentException(
                'Nenhum Pokémon do seu time está de pé. Cure o time de graça no Centro Pokémon (Team Builder).'
            );
        }
        $especies = new EspecieRepository($this->pdo);
        if ($lider !== null) {
            $sorteadas = $especies->sortearParecidas(
                $lider->getQuantidade(),
                $lider->getForca(),
                (int) round($lider->getForca() * self::FOLGA_DO_LIDER),
                $lider->getTipo()
            );
        } else {
            $media = intdiv(array_sum(array_map(fn(Pokemon $p) => $p->getEspecie()->forca(), $dePe)), count($dePe));
            $sorteadas = $especies->sortearParecidas(
                random_int(1, count($dePe)), $media, (int) round($media * self::FOLGA_DE_FORCA)
            );
        }

        // usuário 0 = computador; Pokémon novo nasce com o HP cheio
        $oponente = array_map(fn(Especie $e) => Pokemon::novoDaEspecie(0, $e), $sorteadas);
        $golpesOponente = array_map(fn(Pokemon $p) => $golpes->daEspecie($p->getEspecie()->getId()), $oponente);

        return new Batalha(
            $time,
            $golpesTime,
            $oponente,
            $golpesOponente,
            $lider?->getNome() ?? self::TREINADORES[array_rand(self::TREINADORES)],
            $lider
        );
    }

    /**
     * Grava a batalha que acabou. TRANSAÇÃO (DTL): ou grava tudo, ou nada.
     *   1) batalhas         -> o trigger trg_batalhas_premio paga o prêmio
     *                          (lider_id diz se foi contra um líder de ginásio);
     *   2) batalha_turnos   -> o log de cada ação;
     *   3) pokemons         -> o HP e a condição com que o time saiu da batalha.
     * Devolve o id da batalha gravada.
     */
    public function salvar(int $usuarioId, Batalha $b): int
    {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare(
                'INSERT INTO batalhas (usuario_id, lider_id, oponente_nome, vencedor, dinheiro_ganho, total_turnos)
                 VALUES (:u, :lider, :oponente, :vencedor, :dinheiro, :turnos)'
            )->execute([
                'u'        => $usuarioId,
                'lider'    => $b->getLider()?->getId(), // NULL = treinador comum
                'oponente' => $b->getOponenteNome(),
                'vencedor' => $b->getVencedor(),
                'dinheiro' => $b->premio(),
                'turnos'   => $b->getNumero(),
            ]);
            $batalhaId = (int) $this->pdo->lastInsertId();

            $st = $this->pdo->prepare(
                'INSERT INTO batalha_turnos (batalha_id, turno, ator, acao, dano, hp_restante)
                 VALUES (:b, :turno, :ator, :acao, :dano, :hp)'
            );
            foreach ($b->getTurnos() as $t) {
                $st->execute([
                    'b'     => $batalhaId,
                    'turno' => $t->getNumero(),
                    'ator'  => $t->getAtor(),
                    'acao'  => $t->getAcao(),
                    'dano'  => $t->getDano(),
                    'hp'    => $t->getHpRestante(),
                ]);
            }

            // desmaiado e status de quem ficou com 0 HP: o trigger trg_pokemons_hp_update ajusta
            $st = $this->pdo->prepare(
                'UPDATE pokemons SET hp_atual = :hp, status = :status WHERE id = :id AND usuario_id = :u'
            );
            foreach ($b->getJogador() as $p) {
                $st->execute([
                    'hp'     => $p->getHpAtual(),
                    'status' => $p->getStatus(),
                    'id'     => $p->getId(),
                    'u'      => $usuarioId,
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
