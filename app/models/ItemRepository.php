<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class ItemRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    /** Itens à venda, lidos da VIEW vw_loja (já vem ordenada). @return Item[] */
    public function listarLoja(): array
    {
        $linhas = $this->pdo->query('SELECT * FROM vw_loja')->fetchAll();
        return array_map([Item::class, 'fromRow'], $linhas);
    }

    /** Mochila do jogador, lida da VIEW vw_inventario (só itens com quantidade > 0). */
    public function inventario(int $usuarioId): array
    {
        $st = $this->pdo->prepare(
            'SELECT item_id, nome, categoria, descricao, quantidade
               FROM vw_inventario
              WHERE usuario_id = :u
              ORDER BY nome'
        );
        $st->execute(['u' => $usuarioId]);
        return $st->fetchAll();
    }

    /**
     * Compra pela PROCEDURE sp_comprar_item (triggers.sql). Ela faz tudo numa
     * TRANSAÇÃO: confere o saldo, debita, coloca na mochila e grava o log.
     * Se faltar saldo, a procedure dá SIGNAL '45000', que chega aqui como
     * PDOException (o controller transforma em mensagem para o jogador).
     */
    public function comprar(int $usuarioId, int $itemId, int $quantidade): void
    {
        $st = $this->pdo->prepare('CALL sp_comprar_item(:u, :i, :q)');
        $st->execute(['u' => $usuarioId, 'i' => $itemId, 'q' => $quantidade]);
        $st->closeCursor(); // libera a conexão para a próxima consulta
    }
}
