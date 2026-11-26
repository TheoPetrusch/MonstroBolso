<?php

namespace App\Core;

/**
 * Classe ABSTRATA (não dá para instanciar) com o que todo controller usa.
 * Os controllers HERDAM dela (class TimeController extends BaseController).
 * Métodos protected: só a própria classe e as filhas podem chamar.
 */
abstract class BaseController
{
    /** Responde em JSON (transporte de dados com o JavaScript) com o status HTTP. */
    protected function json(mixed $dados, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Mostra uma tela (o "V" do MVC): app/views/{nome}.php.
     * extract() transforma ['usuario' => ...] na variável $usuario dentro da view;
     * $base (caminho do site) vai para todas as views.
     */
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

    /** Manda o navegador para outra página (ex.: sem login, volta para o login). */
    protected function redirecionar(string $rota): void
    {
        header('Location: ' . $this->basePath() . $rota);
    }

    /** Id do usuário logado ou null (sessão iniciada no index.php). */
    protected function usuarioIdLogado(): ?int
    {
        return isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : null;
    }

    /** Para rotas da API: responde 401 e devolve null se não houver login. */
    protected function exigirLoginApi(): ?int
    {
        $id = $this->usuarioIdLogado();
        if ($id === null) {
            $this->json(['ok' => false, 'erro' => 'Não autenticado.'], 401);
        }
        return $id;
    }
}
