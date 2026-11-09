<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Database;

class PingController extends BaseController
{
    public function ping(): void
    {
        $this->json(['ok' => true]);
    }

    // Testa a conexão com o banco
    public function db(): void
    {
        $pdo = Database::getConnection();
        $versao = $pdo->query('SELECT VERSION() AS v')->fetch();
        $this->json(['ok' => true, 'mysql' => $versao['v']]);
    }
}