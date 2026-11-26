<?php

namespace App\Core;

use PDO;

/**
 * Conexão com o MySQL via PDO (padrão SINGLETON).
 * Só existe UMA conexão por requisição: a primeira chamada a getConnection()
 * cria o PDO, as próximas devolvem o mesmo objeto. Todos os Repository usam ela.
 */
class Database
{
    /** A única conexão (static = pertence à classe, não a um objeto). */
    private static ?PDO $instance = null;

    /** Construtor privado: ninguém faz "new Database()", só getConnection(). */
    private function __construct()
    {
    }

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            // dados do banco: do arquivo .env (se existir) ou o padrão do XAMPP
            $env = self::loadEnv();

            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=utf8mb4',
                $env['DB_HOST'] ?? 'localhost',
                $env['DB_NAME'] ?? 'monstrobolso'
            );

            self::$instance = new PDO($dsn, $env['DB_USER'] ?? 'root', $env['DB_PASS'] ?? '', [
                    // erro de SQL vira PDOException (dá para usar try/catch)
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    // fetch() devolve array com o nome das colunas: $linha['nome']
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    // prepared statements de verdade no MySQL (proteção contra SQL Injection)
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }

        return self::$instance;
    }

    /** Lê o .env da raiz do projeto (DB_HOST=..., DB_USER=...) como array. */
    private static function loadEnv(): array
    {
        $arquivo = dirname(__DIR__, 2) . '/.env';
        return file_exists($arquivo) ? parse_ini_file($arquivo) : [];
    }
}
