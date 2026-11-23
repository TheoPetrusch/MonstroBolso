<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class GolpeRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    /** @return Golpe[] */
    public function listar(): array
    {
        $linhas = $this->pdo->query(
            'SELECT g.*, t.nome AS tipo
               FROM golpes g JOIN tipos t ON t.id = g.tipo_id
              ORDER BY g.id'
        )->fetchAll();
        return array_map([Golpe::class, 'fromRow'], $linhas);
    }

    /**
     * Os golpes que uma espécie conhece (tabela especie_golpes).
     * Se a espécie não tiver nenhum cadastrado, usa o Tackle, para a
     * batalha nunca ficar sem golpe.
     * @return Golpe[]
     */
    public function daEspecie(int $especieId): array
    {
        $st = $this->pdo->prepare(
            'SELECT g.*, t.nome AS tipo
               FROM especie_golpes eg
               JOIN golpes g ON g.id = eg.golpe_id
               JOIN tipos t ON t.id = g.tipo_id
              WHERE eg.especie_id = :e
              ORDER BY g.poder'
        );
        $st->execute(['e' => $especieId]);
        $linhas = $st->fetchAll();

        if (!$linhas) {
            $linhas = $this->pdo->query(
                "SELECT g.*, t.nome AS tipo
                   FROM golpes g JOIN tipos t ON t.id = g.tipo_id
                  WHERE g.nome = 'Tackle'"
            )->fetchAll();
        }
        return array_map([Golpe::class, 'fromRow'], $linhas);
    }

    /**
     * Troca os golpes de uma espécie pelos informados.
     * Deve ser chamado dentro de uma transação (o importador abre uma).
     * @param Golpe[] $golpes
     */
    public function definirDaEspecie(int $especieId, array $golpes): void
    {
        $this->pdo->prepare('DELETE FROM especie_golpes WHERE especie_id = :e')
            ->execute(['e' => $especieId]);

        $st = $this->pdo->prepare(
            'INSERT INTO especie_golpes (especie_id, golpe_id) VALUES (:e, :g)'
        );
        foreach ($golpes as $g) {
            $st->execute(['e' => $especieId, 'g' => $g->getId()]);
        }
    }
}
