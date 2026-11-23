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

    /** Item com todos os efeitos (cura_hp, cura_percentual...), direto da tabela. */
    public function buscarPorId(int $id): ?Item
    {
        $st = $this->pdo->prepare('SELECT * FROM itens WHERE id = :id');
        $st->execute(['id' => $id]);
        $linha = $st->fetch();
        return $linha ? Item::fromRow($linha) : null;
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

    /** O jogador tem pelo menos 1 desse item na mochila? */
    public function temNaMochila(int $usuarioId, int $itemId): bool
    {
        $st = $this->pdo->prepare(
            'SELECT quantidade FROM inventario WHERE usuario_id = :u AND item_id = :i'
        );
        $st->execute(['u' => $usuarioId, 'i' => $itemId]);
        return (int) $st->fetchColumn() > 0;
    }

    /** Tira 1 unidade do item da mochila ("quantidade > 0" impede ficar negativo). */
    public function gastarUm(int $usuarioId, int $itemId): void
    {
        $this->pdo->prepare(
            'UPDATE inventario SET quantidade = quantidade - 1
              WHERE usuario_id = :u AND item_id = :i AND quantidade > 0'
        )->execute(['u' => $usuarioId, 'i' => $itemId]);
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
