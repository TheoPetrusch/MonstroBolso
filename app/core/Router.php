<?php

namespace App\Core;

use Throwable;

class Router
{
    private array $routes = [];

    public function get(string $path, array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, array $handler): void
    {
        $this->routes[] = compact('method', 'path', 'handler');
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        if ($base !== '' && stripos($path, $base) === 0) {
            $path = substr($path, strlen($base));
        }
        $path = '/' . trim($path, '/');

        try {
            foreach ($this->routes as $route) {
                if ($route['method'] !== $method) {
                    continue;
                }

                // Converte /api/pokemon/{id} em regex
                $regex = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $route['path']);

                if (preg_match('#^' . $regex . '$#', $path, $matches)) {
                    $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                    [$classe, $metodo] = $route['handler'];
                    (new $classe())->$metodo($params);
                    return;
                }
            }

            $this->erro(404, 'Rota não encontrada');
        } catch (Throwable $e) {
            // SEGURANÇA: o detalhe do erro (SQL, caminho de arquivo...) não vai
            // para o navegador; fica no log do Apache (xampp/apache/logs/error.log).
            error_log('[MonstroBolso] ' . $e);
            $this->erro(500, 'Erro interno no servidor. Tente de novo.');
        }
    }

    private function erro(int $status, string $mensagem): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'erro' => $mensagem], JSON_UNESCAPED_UNICODE);
    }
}
