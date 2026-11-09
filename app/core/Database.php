<?php
namespace App\Core;

use PDO;

class Database
{
    private static ?PDO $instance = null;
    private function __construct() {}

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $env = self::loadEnv();

            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=utf8mb4',
                $env['DB_HOST'] ?? 'localhost',
                $env['DB_NAME'] ?? 'monstrobolso'
            );

            self::$instance = new PDO($dsn, $env['DB_USER'] ?? 'root', $env['DB_PASS'] ?? '', [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }

        return self::$instance;
    }

    private static function loadEnv(): array
    {
        $arquivo = dirname(__DIR__, 2) . '/.env';
        return file_exists($arquivo) ? parse_ini_file($arquivo) : [];
    }
}