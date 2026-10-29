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
        $dados['base'] = $this->basePath();
        extract($dados);
        require dirname(__DIR__) . '/views/' . $nome . '.php';
    }

    /** Lê o corpo da requisição (JSON enviado pelo fetch) como array. */
    protected function corpoJson(): array
    {
        $dados = json_decode(file_get_contents('php://input'), true);
        return is_array($dados) ? $dados : [];
    }

    /** Ex.: "/MonstroBolso/public" - usado pelas views e pelo JS. */
    protected function basePath(): string
    {
        return rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    }

    protected function redirecionar(string $rota): void
    {
        header('Location: ' . $this->basePath() . $rota);
    }
}
