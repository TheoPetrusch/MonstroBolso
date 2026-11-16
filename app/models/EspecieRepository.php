<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class EspecieRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    private const SELECT = 'SELECT e.*, t.nome AS tipo
                            FROM especies e JOIN tipos t ON t.id = e.tipo_id';

    /** @return Especie[] */
    public function listar(): array
    {
        $linhas = $this->pdo->query(self::SELECT . ' ORDER BY e.pokeapi_id')->fetchAll();
        return array_map([Especie::class, 'fromRow'], $linhas);
    }

    public function buscarPorId(int $id): ?Especie
    {
        $st = $this->pdo->prepare(self::SELECT . ' WHERE e.id = :id');
        $st->execute(['id' => $id]);
        $linha = $st->fetch();
        return $linha ? Especie::fromRow($linha) : null;
    }

    /**
     * Insere ou atualiza uma espécie pelo pokeapi_id (UPSERT) e devolve o id.
     * Atualizar em vez de apagar mantém o id, então os Pokémon dos jogadores
     * continuam apontando para a mesma espécie.
     */
    public function salvarDaApi(array $d): int
    {
        $this->pdo->prepare(
            'INSERT INTO especies
                    (pokeapi_id, nome, tipo_id, hp, ataque, defesa, sp_atk, sp_def, velocidade, sprite_url)
             VALUES (:pid, :nome, (SELECT id FROM tipos WHERE nome = :tipo),
                     :hp, :atk, :def, :spa, :spd, :vel, :sprite)
             ON DUPLICATE KEY UPDATE
                    nome = VALUES(nome), tipo_id = VALUES(tipo_id), hp = VALUES(hp),
                    ataque = VALUES(ataque), defesa = VALUES(defesa), sp_atk = VALUES(sp_atk),
                    sp_def = VALUES(sp_def), velocidade = VALUES(velocidade),
                    sprite_url = VALUES(sprite_url)'
        )->execute([
            'pid'    => $d['pokeapi_id'],
            'nome'   => $d['nome'],
            'tipo'   => $d['tipo'],
            'hp'     => $d['hp'],
            'atk'    => $d['ataque'],
            'def'    => $d['defesa'],
            'spa'    => $d['sp_atk'],
            'spd'    => $d['sp_def'],
            'vel'    => $d['velocidade'],
            'sprite' => $d['sprite_url'],
        ]);

        $st = $this->pdo->prepare('SELECT id FROM especies WHERE pokeapi_id = :pid');
        $st->execute(['pid' => $d['pokeapi_id']]);
        return (int) $st->fetchColumn();
    }

    /**
     * Liga a espécie à sua evolução. A pedra usada é a pedra do tipo da
     * espécie (itens.tipo_id), então a regra fica no banco e não no PHP.
     */
    public function definirEvolucao(int $pokeapiDe, int $pokeapiPara): void
    {
        $this->pdo->prepare(
            'UPDATE especies e
               JOIN especies prox ON prox.pokeapi_id = :para
               LEFT JOIN itens i ON i.categoria = \'evolucao\' AND i.tipo_id = e.tipo_id
                SET e.evolui_para_id = prox.id,
                    e.item_evolucao_id = i.id
              WHERE e.pokeapi_id = :de'
        )->execute(['de' => $pokeapiDe, 'para' => $pokeapiPara]);
    }
}
