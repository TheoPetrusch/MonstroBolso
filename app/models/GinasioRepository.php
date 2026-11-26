<?php

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Progresso do jogador nos ginásios.
 * Nada é guardado em contador: tudo sai do LOG de batalhas pela
 * VIEW vw_progresso_ginasio (ver o final do database/monstrobolso.sql).
 */
class GinasioRepository
{
    /** Vitórias contra treinadores comuns para poder desafiar o próximo líder. */
    public const VITORIAS_NECESSARIAS = 2;

    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    /**
     * Ex.: ['insignias' => 2, 'vitorias' => 7, 'necessarias' => 10,
     *       'total_lideres' => 8, 'liberado' => false, 'proximo' => Lider|null]
     */
    public function progresso(int $usuarioId): array
    {
        $st = $this->pdo->prepare(
            'SELECT insignias, vitorias_desde_lider FROM vw_progresso_ginasio WHERE usuario_id = :u'
        );
        $st->execute(['u' => $usuarioId]);
        $linha = $st->fetch() ?: ['insignias' => 0, 'vitorias_desde_lider' => 0];

        $insignias = (int) $linha['insignias'];
        $vitorias = (int) $linha['vitorias_desde_lider'];
        $proximo = $this->liderDaOrdem($insignias + 1); // sempre em ordem: não dá para pular

        return [
            'insignias' => $insignias,
            'vitorias' => min($vitorias, self::VITORIAS_NECESSARIAS),
            'necessarias' => self::VITORIAS_NECESSARIAS,
            'total_lideres' => (int) $this->pdo->query('SELECT COUNT(*) FROM lideres')->fetchColumn(),
            'liberado' => $proximo !== null && $vitorias >= self::VITORIAS_NECESSARIAS,
            'proximo' => $proximo,
        ];
    }

    private function liderDaOrdem(int $ordem): ?Lider
    {
        $st = $this->pdo->prepare(
            'SELECT l.*, t.nome AS tipo,
                    (SELECT COUNT(*) FROM lider_pokemons lp WHERE lp.lider_id = l.id) AS quantidade
               FROM lideres l JOIN tipos t ON t.id = l.tipo_id
              WHERE l.ordem = :o'
        );
        $st->execute(['o' => $ordem]);
        $linha = $st->fetch();
        return $linha ? Lider::fromRow($linha) : null;
    }
}
