<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Exceptions\CredenciaisInvalidasException;
use App\Models\Usuario;
use App\Models\UsuarioRepository;
use InvalidArgumentException;
use PDOException;

class AuthController extends BaseController
{
    private UsuarioRepository $repo;

    public function __construct()
    {
        $this->repo = new UsuarioRepository();
    }

    // paginas html

    public function paginaLogin(): void
    {
        if (isset($_SESSION['usuario_id'])) {
            $this->redirecionar('/home');
            return;
        }
        $this->view('login');
    }

    public function paginaHome(): void
    {
        $u = $this->usuarioLogado();
        if (!$u) {
            $this->redirecionar('/');
            return;
        }
        $this->view('home', ['usuario' => $u->paraArray()]);
    }

    // API json

    public function login(): void
    {
        try {
            $dados = $this->corpoJson();
            $email = trim((string) ($dados['email'] ?? ''));
            $senha = (string) ($dados['senha'] ?? '');

            if ($email === '' || $senha === '') {
                throw new InvalidArgumentException('Informe e-mail e senha.');
            }

            $u = $this->repo->buscarPorEmail($email);
            if (!$u || !$u->verificarSenha($senha)) {
                throw new CredenciaisInvalidasException();
            }

            session_regenerate_id(true);
            $_SESSION['usuario_id'] = $u->getId();

            $this->json(['ok' => true, 'usuario' => $u->paraArray()]);
        } catch (CredenciaisInvalidasException $e) {
            $this->json(['ok' => false, 'erro' => $e->getMessage()], $e->getCode());
        } catch (InvalidArgumentException $e) {
            $this->json(['ok' => false, 'erro' => $e->getMessage()], 422);
        }
    }

    public function registrar(): void
    {
        try {
            $d = $this->corpoJson();
            $u = Usuario::novo(
                (string) ($d['nome'] ?? ''),
                (string) ($d['email'] ?? ''),
                (string) ($d['senha'] ?? '')
            );

            if ($this->repo->buscarPorEmail($u->getEmail())) {
                throw new InvalidArgumentException('Este e-mail já está cadastrado.');
            }

            $u = $this->repo->criar($u);
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = $u->getId();

            $this->json(['ok' => true, 'usuario' => $u->paraArray()], 201);
        } catch (InvalidArgumentException $e) {
            $this->json(['ok' => false, 'erro' => $e->getMessage()], 422);
        } catch (PDOException $e) {
            $this->json(['ok' => false, 'erro' => 'Erro ao gravar no banco.'], 500);
        }
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
        $this->json(['ok' => true]);
    }

    public function eu(): void
    {
        $u = $this->usuarioLogado();
        if (!$u) {
            $this->json(['ok' => false, 'erro' => 'Não autenticado.'], 401);
            return;
        }
        $this->json(['ok' => true, 'usuario' => $u->paraArray()]);
    }

    public function trocarPerfil(): void
    {
        try {
            $logado = $this->usuarioLogado();
            if (!$logado) {
                $this->json(['ok' => false, 'erro' => 'Não autenticado.'], 401);
                return;
            }
            if (!$logado->isAdmin()) {
                $this->json(['ok' => false, 'erro' => 'Apenas admin troca perfis.'], 403);
                return;
            }

            $d = $this->corpoJson();
            $alvo = $this->repo->buscarPorId((int) ($d['usuario_id'] ?? 0));
            if (!$alvo) {
                throw new InvalidArgumentException('Usuário não encontrado.');
            }

            $alvo->setPerfil((string) ($d['perfil'] ?? '')); // valida o valor
            $this->repo->alterarPerfil($alvo->getId(), $alvo->getPerfil());

            $this->json(['ok' => true, 'usuario' => $alvo->paraArray()]);
        } catch (InvalidArgumentException $e) {
            $this->json(['ok' => false, 'erro' => $e->getMessage()], 422);
        }
    }

    private function usuarioLogado(): ?Usuario
    {
        $id = $_SESSION['usuario_id'] ?? null;
        return $id ? $this->repo->buscarPorId((int) $id) : null;
    }
}
