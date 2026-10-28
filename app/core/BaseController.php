<?php
namespace App\Core;

abstract class BaseController
{
    protected function json(mixed $dados, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    }

    protected function view(string $nome, array $dados = []): void
    {
        extract($dados);
        require dirname(__DIR__) . '/views/' . $nome . '.php';
    }
}