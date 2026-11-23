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
     * Sorteia espécies para o time do computador com FORÇA PARECIDA com $forca
     * (força = soma dos 6 atributos). Quem fica até $folga de distância tem a
     * mesma chance (vale 0 no ORDER BY e o RAND() desempata); se não houver
     * espécies suficientes nessa faixa, completa com as mais próximas.
     * O CAST para SIGNED evita erro do MySQL ao subtrair números UNSIGNED.
     * @return Especie[]
     */
    public function sortearParecidas(int $quantidade, int $forca, int $folga): array
    {
        $st = $this->pdo->prepare(
            self::SELECT . '
             ORDER BY GREATEST(
                        ABS(CAST(e.hp + e.ataque + e.defesa + e.sp_atk + e.sp_def + e.velocidade AS SIGNED) - :forca)
                        - :folga, 0),
                      RAND()
             LIMIT ' . (int) $quantidade
        );
        $st->execute(['forca' => $forca, 'folga' => $folga]);
        return array_map([Especie::class, 'fromRow'], $st->fetchAll());
    }

    /**
     * Ids das espécies evoluídas que o jogador já desbloqueou (evoluindo
     * um Pokémon com pedra). O trigger trg_pokemons_desbloqueia preenche a tabela.
     * @return int[]
     */
    public function idsDesbloqueados(int $usuarioId): array
    {
        $st = $this->pdo->prepare(
            'SELECT especie_id FROM especies_desbloqueadas WHERE usuario_id = :u'
        );
        $st->execute(['u' => $usuarioId]);
        return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
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
                    (pokeapi_id, nome, tipo_id, hp, ataque, defesa, sp_atk, sp_def, velocidade,
                     sprite_url, evolui_de_pokeapi_id)
             VALUES (:pid, :nome, (SELECT id FROM tipos WHERE nome = :tipo),
                     :hp, :atk, :def, :spa, :spd, :vel, :sprite, :pai)
             ON DUPLICATE KEY UPDATE
                    nome = VALUES(nome), tipo_id = VALUES(tipo_id), hp = VALUES(hp),
                    ataque = VALUES(ataque), defesa = VALUES(defesa), sp_atk = VALUES(sp_atk),
                    sp_def = VALUES(sp_def), velocidade = VALUES(velocidade),
                    sprite_url = VALUES(sprite_url),
                    evolui_de_pokeapi_id = VALUES(evolui_de_pokeapi_id)'
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
            'pai'    => $d['evolui_de'],
        ]);

        $st = $this->pdo->prepare('SELECT id FROM especies WHERE pokeapi_id = :pid');
        $st->execute(['pid' => $d['pokeapi_id']]);
        return (int) $st->fetchColumn();
    }

    /**
     * Liga as evoluções do banco INTEIRO, não só as da geração importada agora.
     * Assim a ordem de importação não importa: se a 2ª geração (Pichu) entrar
     * depois da 1ª (Pikachu), o Pikachu passa a evoluir do Pichu.
     *   1) evolui_de_id: acha o pai pelo pokeapi_id guardado (evolui_de_pokeapi_id);
     *   2) evolui_para_id / item_evolucao_id: preenchidos só para quem tem
     *      UMA evolução (com várias, como Eevee, a vw_evolucoes resolve).
     * Deve ser chamado dentro de uma transação (o importador abre uma).
     * @return int total de evoluções ligadas no banco
     */
    public function ligarEvolucoes(): int
    {
        // 1) pai de cada espécie (NULL se o pai ainda não foi importado)
        $this->pdo->exec(
            'UPDATE especies filho
               LEFT JOIN especies pai ON pai.pokeapi_id = filho.evolui_de_pokeapi_id
                SET filho.evolui_de_id = pai.id
              WHERE filho.evolui_de_pokeapi_id IS NOT NULL'
        );

        // 2) evolução única de cada pai; a pedra é a do tipo da evolução
        $pais = $this->pdo->query(
            'SELECT evolui_de_id AS pai, MIN(id) AS filho, COUNT(*) AS total
               FROM especies
              WHERE evolui_de_id IS NOT NULL
              GROUP BY evolui_de_id'
        )->fetchAll();

        $st = $this->pdo->prepare(
            'UPDATE especies e
               LEFT JOIN especies prox ON prox.id = :filho
               LEFT JOIN itens i ON i.categoria = \'evolucao\' AND i.tipo_id = prox.tipo_id
                SET e.evolui_para_id = prox.id,
                    e.item_evolucao_id = i.id
              WHERE e.id = :pai'
        );
        $total = 0;
        foreach ($pais as $p) {
            $st->execute([
                'pai'   => $p['pai'],
                'filho' => (int) $p['total'] === 1 ? $p['filho'] : null,
            ]);
            $total += (int) $p['total'];
        }
        return $total;
    }

    /** pokeapi_id de todas as espécies já importadas. @return int[] */
    public function pokeapiIds(): array
    {
        return array_map('intval', $this->pdo->query(
            'SELECT pokeapi_id FROM especies'
        )->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * pokeapi_id de todas as espécies que têm evolução no banco
     * (usado para saber quem é 1º estágio na hora de escolher golpes).
     * @return int[]
     */
    public function pokeapiIdsComEvolucao(): array
    {
        return array_map('intval', $this->pdo->query(
            'SELECT DISTINCT evolui_de_pokeapi_id FROM especies
              WHERE evolui_de_pokeapi_id IS NOT NULL'
        )->fetchAll(PDO::FETCH_COLUMN));
    }
}
