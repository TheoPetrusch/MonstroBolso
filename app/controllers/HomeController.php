<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\UsuarioRepository;

/** Página inicial do jogador logado (menu do jogo). */
class HomeController extends BaseController
{
    private UsuarioRepository $repo;

    public function __construct()
    {
        $this->repo = new UsuarioRepository();
    }

    /** GET /home: menu com Team Builder, Batalha, Poké Mart e a importação de gerações. */
    public function pagina(): void
    {
        $id = $this->usuarioIdLogado();
        $u = $id !== null ? $this->repo->buscarPorId($id) : null;

        if (!$u) {
            // sessão aponta para um usuário que não existe mais (ex.: o banco
            // foi recriado). Sem limpar a sessão, o login (/) manda de volta
            // para /home, que manda para /... e o navegador entra em loop.
            $_SESSION = [];
            session_destroy();
            $this->redirecionar('/');
            return;
        }
        $this->view('home', ['usuario' => $u->paraArray()]);
    }
}
