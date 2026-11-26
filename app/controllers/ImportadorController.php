<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\ImportadorPokemon;
use InvalidArgumentException;
use RuntimeException;

/** Rota que importa uma geração de Pokémon da PokéAPI para o banco (qualquer usuário logado). */
class ImportadorController extends BaseController
{
    public function importar(): void
    {
        if ($this->exigirLoginApi() === null)
            return;

        $geracao = (int) ($this->corpoJson()['geracao'] ?? 0);
        if (!isset(ImportadorPokemon::GERACOES[$geracao])) {
            $this->json(['ok' => false, 'erro' => 'Escolha uma geração de 1 a 9.'], 422);
            return;
        }

        // A importação demora: libera a sessão para o jogador poder usar
        // outras abas enquanto isso, e aumenta o tempo limite do PHP.
        session_write_close();
        set_time_limit(900); // 15 minutos: a 5ª geração tem 156 Pokémon, 2 consultas cada

        try {
            $resultado = (new ImportadorPokemon())->importarGeracao($geracao);
            $this->json(['ok' => true, 'geracao' => $geracao] + $resultado);
        } catch (InvalidArgumentException $e) {
            $this->json(['ok' => false, 'erro' => $e->getMessage()], 422);
        } catch (RuntimeException $e) {
            // falha na PokéAPI (internet ou certificado SSL): 502 = erro no serviço externo
            $this->json(['ok' => false, 'erro' => $e->getMessage()], 502);
        }
    }
}
