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
}
