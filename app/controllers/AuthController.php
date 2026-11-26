<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Exceptions\CredenciaisInvalidasException;
use App\Models\Usuario;
use App\Models\UsuarioRepository;
use InvalidArgumentException;
use PDOException;

/**
 * CONTROLE DE USUÁRIO: tela de login, cadastro, login, logout.
 * Quem está logado fica guardado na SESSÃO ($_SESSION['usuario_id']);
 * cada jogador tem o próprio time, dinheiro e mochila.
 */
class AuthController extends BaseController
{
    private UsuarioRepository $repo;

    public function __construct()
    {
        $this->repo = new UsuarioRepository();
    }

    // paginas html

    /** GET / : já logado vai direto para a Home; senão mostra o login. */
    public function paginaLogin(): void
    {
        if (isset($_SESSION['usuario_id'])) {
            $this->redirecionar('/home');
            return;
        }
        $this->view('login');
    }

    // API json

    /** POST /api/login  corpo JSON: { "email": "...", "senha": "..." } */
    public function login(): void
    {
        try {
            $dados = $this->corpoJson();
            $email = trim((string) ($dados['email'] ?? ''));
            $senha = (string) ($dados['senha'] ?? '');

            if ($email === '' || $senha === '') {
                throw new InvalidArgumentException('Informe e-mail e senha.');
            }

            // mesma mensagem para e-mail inexistente e senha errada:
            // assim ninguém descobre quais e-mails têm conta
            $u = $this->repo->buscarPorEmail($email);
            if (!$u || !$u->verificarSenha($senha)) { // verificarSenha usa password_verify
                throw new CredenciaisInvalidasException(); // exceção customizada
            }

            // SEGURANÇA: novo id de sessão no login (evita "session fixation")
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = $u->getId();

            $this->json(['ok' => true, 'usuario' => $u->paraArray()]);
        } catch (CredenciaisInvalidasException $e) {
            // getCode() = 401, definido dentro da exceção
            $this->json(['ok' => false, 'erro' => $e->getMessage()], $e->getCode());
        } catch (InvalidArgumentException $e) {
            $this->json(['ok' => false, 'erro' => $e->getMessage()], 422);
        }
    }

    /** POST /api/registrar  corpo JSON: { "nome", "email", "senha" }. Já entra logado. */
    public function registrar(): void
    {
        try {
            $d = $this->corpoJson();
            // Usuario::novo valida nome, e-mail e senha (lança InvalidArgumentException)
            // e guarda a senha com password_hash (nunca a senha pura)
            $u = Usuario::novo(
                (string) ($d['nome'] ?? ''),
                (string) ($d['email'] ?? ''),
                (string) ($d['senha'] ?? '')
            );

            if ($this->repo->buscarPorEmail($u->getEmail())) {
                throw new InvalidArgumentException('Este e-mail já está cadastrado.');
            }

            $u = $this->repo->criar($u); // cria o usuário e o time dele (transação)
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = $u->getId();

            $this->json(['ok' => true, 'usuario' => $u->paraArray()], 201);
        } catch (InvalidArgumentException $e) {
            $this->json(['ok' => false, 'erro' => $e->getMessage()], 422);
        } catch (PDOException $e) {
            // 1062 = e-mail repetido (UNIQUE): acontece se dois cadastros iguais chegam juntos
            if (($e->errorInfo[1] ?? 0) === 1062) {
                $this->json(['ok' => false, 'erro' => 'Este e-mail já está cadastrado.'], 422);
                return;
            }
            error_log('[MonstroBolso] ' . $e); // SEGURANÇA: detalhe só no log
            $this->json(['ok' => false, 'erro' => 'Erro ao gravar no banco. Tente de novo.'], 500);
        }
    }

    /** POST /api/logout: apaga a sessão (o jogador sai). */
    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
        $this->json(['ok' => true]);
    }

    /** GET /api/eu: dados de quem está logado (ou 401). */
    public function eu(): void
    {
        $u = $this->usuarioLogado();
        if (!$u) {
            $this->json(['ok' => false, 'erro' => 'Não autenticado.'], 401);
            return;
        }
        $this->json(['ok' => true, 'usuario' => $u->paraArray()]);
    }

    private function usuarioLogado(): ?Usuario
    {
        $id = $_SESSION['usuario_id'] ?? null;
        return $id ? $this->repo->buscarPorId((int) $id) : null;
    }
}
