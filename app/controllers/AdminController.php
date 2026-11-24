<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Usuario;
use App\Models\UsuarioRepository;
use InvalidArgumentException;

/**
 * CONTROLE DE USUÁRIO: tela do admin para trocar o perfil dos usuários
 * (jogador <-> admin). Cada troca vai para o log log_perfis pelo trigger
 * trg_usuarios_log_perfil. Só quem é admin acessa.
 */
class AdminController extends BaseController
{
    private UsuarioRepository $usuarios;

    public function __construct()
    {
        $this->usuarios = new UsuarioRepository();
    }

    // ---------- página ----------
    public function pagina(): void
    {
        if (!$this->adminLogado()) {
            $this->redirecionar('/home'); // jogador comum não entra
            return;
        }
        $this->view('admin');
    }

    // ---------- API (JSON) ----------

    /** Lista de usuários + histórico de trocas de perfil. */
    public function dados(): void
    {
        $admin = $this->adminLogado();
        if (!$admin) {
            $this->json(['ok' => false, 'erro' => 'Apenas admin.'], 403);
            return;
        }
        $this->json([
            'ok'        => true,
            'eu'        => $admin->getId(),
            'usuarios'  => array_map(fn(Usuario $u) => $u->paraArray(), $this->usuarios->listar()),
            'historico' => $this->usuarios->historicoPerfis(),
        ]);
    }

    /** Corpo JSON: { "usuario_id": 3, "perfil": "admin" } */
    public function trocarPerfil(): void
    {
        $admin = $this->adminLogado();
        if (!$admin) {
            $this->json(['ok' => false, 'erro' => 'Apenas admin troca perfis.'], 403);
            return;
        }

        try {
            $d = $this->corpoJson();
            $alvo = $this->usuarios->buscarPorId((int) ($d['usuario_id'] ?? 0));
            if (!$alvo) {
                throw new InvalidArgumentException('Usuário não encontrado.');
            }
            // o admin não pode tirar o próprio acesso (o sistema ficaria sem admin)
            if ($alvo->getId() === $admin->getId()) {
                throw new InvalidArgumentException('Você não pode trocar o seu próprio perfil.');
            }

            $alvo->setPerfil((string) ($d['perfil'] ?? '')); // o model valida: só 'jogador' ou 'admin'
            $this->usuarios->alterarPerfil($alvo->getId(), $alvo->getPerfil());

            $this->json(['ok' => true, 'usuario' => $alvo->paraArray()]);
        } catch (InvalidArgumentException $e) {
            $this->json(['ok' => false, 'erro' => $e->getMessage()], 422);
        }
    }

    /** Usuário logado, se ele for admin (senão null). */
    private function adminLogado(): ?Usuario
    {
        $id = $this->usuarioIdLogado();
        $u  = $id !== null ? $this->usuarios->buscarPorId($id) : null;
        return $u && $u->isAdmin() ? $u : null;
    }
}
