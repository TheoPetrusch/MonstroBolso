<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Item;
use App\Models\ItemRepository;
use App\Models\UsuarioRepository;
use PDOException;

/** Poké Mart: lista os itens, mostra a mochila e faz a compra. */
class LojaController extends BaseController
{
    private const MAXIMO_POR_COMPRA = 99;

    private ItemRepository $itens;
    private UsuarioRepository $usuarios;

    public function __construct()
    {
        $this->itens    = new ItemRepository();
        $this->usuarios = new UsuarioRepository();
    }

    // ---------- página ----------
    public function pagina(): void
    {
        if ($this->usuarioIdLogado() === null) {
            $this->redirecionar('/');
            return;
        }
        $this->view('loja');
    }

    // ---------- API (JSON) ----------

    /** Tudo que a tela precisa: saldo, itens à venda e mochila. */
    public function dados(): void
    {
        $uid = $this->exigirLoginApi();
        if ($uid === null) return;

        $this->json($this->montarResposta($uid));
    }

    public function comprar(): void
    {
        $uid = $this->exigirLoginApi();
        if ($uid === null) return;

        $d          = $this->corpoJson();
        $itemId     = (int) ($d['item_id'] ?? 0);
        $quantidade = (int) ($d['quantidade'] ?? 1);

        if ($quantidade < 1 || $quantidade > self::MAXIMO_POR_COMPRA) {
            $this->json(['ok' => false, 'erro' => 'Quantidade deve ser de 1 a 99.'], 422);
            return;
        }

        try {
            $this->itens->comprar($uid, $itemId, $quantidade);
            $this->json($this->montarResposta($uid));
        } catch (PDOException $e) {
            // '45000' = erro de regra de negócio lançado pela procedure com SIGNAL
            // (ex.: "Saldo insuficiente."). errorInfo[2] traz só a mensagem.
            if (($e->errorInfo[0] ?? '') === '45000') {
                $this->json(['ok' => false, 'erro' => $e->errorInfo[2]], 422);
                return;
            }
            throw $e; // outro erro de banco: o Router responde 500
        }
    }

    private function montarResposta(int $uid): array
    {
        $usuario = $this->usuarios->buscarPorId($uid);

        return [
            'ok'         => true,
            'saldo'      => $usuario ? $usuario->getPokedollar() : 0,
            'itens'      => array_map(fn(Item $i) => $i->paraArray(), $this->itens->listarLoja()),
            'inventario' => $this->itens->inventario($uid),
        ];
    }
}
