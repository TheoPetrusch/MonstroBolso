<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\UsuarioRepository;
use App\Services\ImportadorPokemon;
use RuntimeException;

/** Rota de admin que importa espécies da PokéAPI para o banco. */
class ImportadorController extends BaseController
{
    private const MAXIMO_POR_VEZ = 200;

    public function importar(): void
    {
        $uid = $this->exigirLoginApi();
        if ($uid === null) return;

        $usuario = (new UsuarioRepository())->buscarPorId($uid);
        if (!$usuario || !$usuario->isAdmin()) {
            $this->json(['ok' => false, 'erro' => 'Apenas admin pode importar Pokémon.'], 403);
            return;
        }

        $d   = $this->corpoJson();
        $de  = (int) ($d['de'] ?? 1);
        $ate = (int) ($d['ate'] ?? 151);
        if ($de < 1 || $ate < $de || $ate - $de >= self::MAXIMO_POR_VEZ) {
            $this->json(['ok' => false, 'erro' => 'Intervalo inválido (até 200 por vez).'], 422);
            return;
        }

        // A importação demora: libera a sessão para o jogador poder usar
        // outras abas enquanto isso, e aumenta o tempo limite do PHP.
        session_write_close();
        set_time_limit(600); // 10 minutos: são 2 consultas por Pokémon, uma de cada vez

        try {
            $resultado = (new ImportadorPokemon())->importar($de, $ate);
            $this->json(['ok' => true] + $resultado);
        } catch (RuntimeException $e) {
            // falha na PokéAPI (internet ou certificado SSL): 502 = erro no serviço externo
            $this->json(['ok' => false, 'erro' => $e->getMessage()], 502);
        }
    }
}
