<?php

namespace App\Models;

use App\Core\Database;
use App\Exceptions\TimeCheioException;
use InvalidArgumentException;
use PDO;

class TimeRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    /** Lê o time pela VIEW vw_time_completo e monta o objeto Time. */
    public function carregar(int $usuarioId): Time
    {
        $st = $this->pdo->prepare(
            'SELECT * FROM vw_time_completo WHERE usuario_id = :u ORDER BY posicao'
        );
        $st->execute(['u' => $usuarioId]);

        $pokemons = array_map([Pokemon::class, 'fromRow'], $st->fetchAll());
        return new Time($usuarioId, $pokemons);
    }

    /**
     * Cria um Pokémon da espécie escolhida e coloca no 1º slot livre.
     * TRANSAÇÃO: se algo falhar, nada fica gravado.
     */
    public function adicionarEspecie(int $usuarioId, Especie $especie): void
    {
        $this->pdo->beginTransaction();
        try {
            // trava a linha do time para dois cliques simultâneos não furarem o limite
            $st = $this->pdo->prepare('SELECT id FROM times WHERE usuario_id = :u FOR UPDATE');
            $st->execute(['u' => $usuarioId]);
            $timeId = $st->fetchColumn();
            if (!$timeId) {
                throw new InvalidArgumentException('Time do usuário não encontrado.');
            }

            $st = $this->pdo->prepare('SELECT posicao FROM time_pokemon WHERE time_id = :t');
            $st->execute(['t' => $timeId]);
            $ocupadas = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));

            // O slot livre é calculado aqui (e não no model Time) porque precisa
            // acontecer DENTRO da transação, com a linha do time travada pelo
            // FOR UPDATE. Fora dela, dois cliques simultâneos poderiam pegar
            // o mesmo slot.
            $livre = null;
            for ($i = 1; $i <= Time::MAXIMO; $i++) {
                if (!in_array($i, $ocupadas, true)) {
                    $livre = $i;
                    break;
                }
            }
            if ($livre === null) {
                throw new TimeCheioException(Time::MAXIMO);
            }

            // a regra de criação fica no model; o repository só grava
            $novo = Pokemon::novoDaEspecie($usuarioId, $especie);

            $this->pdo->prepare(
                'INSERT INTO pokemons (usuario_id, especie_id, nivel, hp_max, hp_atual)
                 VALUES (:u, :e, :nivel, :hp_max, :hp_atual)'
            )->execute([
                'u'        => $novo->getUsuarioId(),
                'e'        => $novo->getEspecie()->getId(),
                'nivel'    => $novo->getNivel(),
                'hp_max'   => $novo->getHpMax(),
                'hp_atual' => $novo->getHpAtual(),
            ]);
            $pokemonId = (int) $this->pdo->lastInsertId();

            // o trigger trg_time_pokemon_dono confere se o Pokémon é do dono do time
            $this->pdo->prepare(
                'INSERT INTO time_pokemon (time_id, posicao, pokemon_id) VALUES (:t, :p, :pk)'
            )->execute(['t' => $timeId, 'p' => $livre, 'pk' => $pokemonId]);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Para cada Pokémon do jogador: as evoluções possíveis, a pedra que
     * cada uma pede e quantas dessas pedras ele tem na mochila (0 = precisa comprar).
     */
    public function opcoesDeEvolucao(int $usuarioId): array
    {
        $st = $this->pdo->prepare(
            'SELECT p.id AS pokemon_id, v.para_nome, v.item_id, v.item_nome,
                    COALESCE(inv.quantidade, 0) AS quantidade
               FROM pokemons p
               JOIN vw_evolucoes v ON v.especie_id = p.especie_id
               LEFT JOIN inventario inv
                      ON inv.usuario_id = p.usuario_id AND inv.item_id = v.item_id
              WHERE p.usuario_id = :u
              ORDER BY p.id, v.para_nome'
        );
        $st->execute(['u' => $usuarioId]);
        return array_map(fn(array $r) => [
            'pokemon_id' => (int) $r['pokemon_id'],
            'para_nome'  => $r['para_nome'],
            'item_id'    => (int) $r['item_id'],
            'item_nome'  => $r['item_nome'],
            'quantidade' => (int) $r['quantidade'],
        ], $st->fetchAll());
    }

    /**
     * Evolui o Pokémon gastando 1 pedra da mochila.
     * TRANSAÇÃO: ou gasta a pedra E troca a espécie, ou não faz nada.
     */
    public function evoluir(int $usuarioId, int $pokemonId, int $itemId): Pokemon
    {
        $this->pdo->beginTransaction();
        try {
            $pokemon = $this->travarPokemon($usuarioId, $pokemonId);

            // a pedra serve para esta espécie? (regra na vw_evolucoes)
            $st = $this->pdo->prepare(
                'SELECT para_especie_id FROM vw_evolucoes
                  WHERE especie_id = :e AND item_id = :i'
            );
            $st->execute(['e' => $pokemon->getEspecie()->getId(), 'i' => $itemId]);
            $paraId = $st->fetchColumn();
            if (!$paraId) {
                throw new InvalidArgumentException('Essa pedra não evolui este Pokémon.');
            }
            $nova = (new EspecieRepository($this->pdo))->buscarPorId((int) $paraId);

            // regra do model (ex.: desmaiado não evolui); lança exceção se não puder
            $pokemon->evoluirPara($nova);
            $this->gastarItem($usuarioId, $itemId);

            $this->pdo->prepare(
                'UPDATE pokemons SET especie_id = :e, hp_max = :max, hp_atual = :atual WHERE id = :id'
            )->execute([
                'e'     => $nova->getId(),
                'max'   => $pokemon->getHpMax(),
                'atual' => $pokemon->getHpAtual(),
                'id'    => $pokemonId,
            ]);

            $this->pdo->commit();
            return $pokemon;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Usa um item de cura (Potion, Antidote, Revive...) num Pokémon do time.
     * TRANSAÇÃO: ou gasta o item E aplica o efeito, ou não faz nada.
     */
    public function usarItem(int $usuarioId, int $pokemonId, int $itemId): Pokemon
    {
        $this->pdo->beginTransaction();
        try {
            $pokemon = $this->travarPokemon($usuarioId, $pokemonId);

            $item = (new ItemRepository($this->pdo))->buscarPorId($itemId);
            if (!$item) {
                throw new InvalidArgumentException('Item não encontrado.');
            }

            // regra do model: lança exceção se o item não tiver efeito
            $pokemon->usarItem($item);
            $this->gastarItem($usuarioId, $itemId);

            // desmaiado é recalculado pelo trigger trg_pokemons_hp_update
            $this->pdo->prepare(
                'UPDATE pokemons SET hp_atual = :hp, status = :status WHERE id = :id'
            )->execute([
                'hp'     => $pokemon->getHpAtual(),
                'status' => $pokemon->getStatus(),
                'id'     => $pokemonId,
            ]);

            $this->pdo->commit();
            return $pokemon;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Trava o Pokémon (FOR UPDATE) e o devolve como objeto.
     * Deve ser chamado dentro de uma transação: assim dois cliques seguidos
     * não gastam dois itens no mesmo Pokémon ao mesmo tempo.
     */
    private function travarPokemon(int $usuarioId, int $pokemonId): Pokemon
    {
        $st = $this->pdo->prepare(
            'SELECT id FROM pokemons WHERE id = :id AND usuario_id = :u FOR UPDATE'
        );
        $st->execute(['id' => $pokemonId, 'u' => $usuarioId]);
        if (!$st->fetchColumn()) {
            throw new InvalidArgumentException('Pokémon não encontrado.');
        }

        $st = $this->pdo->prepare('SELECT * FROM vw_time_completo WHERE pokemon_id = :id');
        $st->execute(['id' => $pokemonId]);
        $linha = $st->fetch();
        if (!$linha) {
            throw new InvalidArgumentException('Pokémon não está no time.');
        }
        return Pokemon::fromRow($linha);
    }

    /** Tira 1 unidade do item da mochila; "quantidade > 0" impede usar o que não tem. */
    private function gastarItem(int $usuarioId, int $itemId): void
    {
        $st = $this->pdo->prepare(
            'UPDATE inventario SET quantidade = quantidade - 1
              WHERE usuario_id = :u AND item_id = :i AND quantidade > 0'
        );
        $st->execute(['u' => $usuarioId, 'i' => $itemId]);
        if ($st->rowCount() === 0) {
            throw new InvalidArgumentException('Você não tem esse item na mochila. Compre no Poké Mart.');
        }
    }

    /** Remove o Pokémon (só se for do usuário). ON DELETE CASCADE limpa o slot. */
    public function remover(int $usuarioId, int $pokemonId): bool
    {
        $st = $this->pdo->prepare('DELETE FROM pokemons WHERE id = :id AND usuario_id = :u');
        $st->execute(['id' => $pokemonId, 'u' => $usuarioId]);
        return $st->rowCount() > 0;
    }
}
