<?php

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * REPOSITORY: toda a conversa com o banco sobre usuários fica aqui (padrão
 * do projeto: o Model guarda as regras, o Repository faz o SQL com PDO).
 * Sempre prepared statements (:email, :id) contra SQL Injection.
 */
class UsuarioRepository
{
    private PDO $pdo;

    /** Pode receber outra conexão (testes); senão usa a do Database (singleton). */
    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    public function buscarPorEmail(string $email): ?Usuario
    {
        $st = $this->pdo->prepare('SELECT * FROM usuarios WHERE email = :email');
        $st->execute(['email' => $email]);
        $linha = $st->fetch();
        return $linha ? Usuario::fromRow($linha) : null;
    }

    public function buscarPorId(int $id): ?Usuario
    {
        $st = $this->pdo->prepare('SELECT * FROM usuarios WHERE id = :id');
        $st->execute(['id' => $id]);
        $linha = $st->fetch();
        return $linha ? Usuario::fromRow($linha) : null;
    }

    /**
     * Cadastro em TRANSAÇÃO (DTL): cria o usuário E o time dele.
     * Se algum dos dois INSERTs falhar, rollBack() desfaz tudo.
     */
    public function criar(Usuario $u): Usuario
    {
        $this->pdo->beginTransaction();
        try {
            $st = $this->pdo->prepare(
                'INSERT INTO usuarios (nome, email, senha_hash, perfil)
                 VALUES (:nome, :email, :hash, :perfil)'
            );
            $st->execute([
                'nome'   => $u->getNome(),
                'email'  => $u->getEmail(),
                'hash'   => $u->getSenhaHash(),
                'perfil' => $u->getPerfil(),
            ]);
            $id = (int) $this->pdo->lastInsertId();

            $this->pdo->prepare('INSERT INTO times (usuario_id) VALUES (:id)')
                ->execute(['id' => $id]);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
        return $this->buscarPorId($id);
    }

    /**
     * Troca o perfil (jogador <-> admin). O trigger trg_usuarios_log_perfil
     * grava a troca na tabela de log log_perfis automaticamente.
     */
    public function alterarPerfil(int $id, string $perfil): void
    {
        $st = $this->pdo->prepare('UPDATE usuarios SET perfil = :perfil WHERE id = :id');
        $st->execute(['perfil' => $perfil, 'id' => $id]);
    }
}
