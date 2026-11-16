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

    /** Remove o Pokémon (só se for do usuário). ON DELETE CASCADE limpa o slot. */
    public function remover(int $usuarioId, int $pokemonId): bool
    {
        $st = $this->pdo->prepare('DELETE FROM pokemons WHERE id = :id AND usuario_id = :u');
        $st->execute(['id' => $pokemonId, 'u' => $usuarioId]);
        return $st->rowCount() > 0;
    }
}