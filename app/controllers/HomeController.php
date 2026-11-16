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

    public function pagina(): void
    {
        $id = $this->usuarioIdLogado();
        $u  = $id !== null ? $this->repo->buscarPorId($id) : null;

        if (!$u) {
            $this->redirecionar('/');
            return;
        }
        $this->view('home', ['usuario' => $u->paraArray()]);
    }
}
