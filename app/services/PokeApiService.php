<?php

namespace App\Services;

use RuntimeException;

/**
 * Consumo da API EXTERNA PokéAPI (https://pokeapi.co) pelo back-end.
 * Usa file_get_contents, que já lê URLs HTTPS no PHP do XAMPP
 * (não precisa da extensão curl).
 */
class PokeApiService
{
    private const URL = 'https://pokeapi.co/api/v2/';
    private const TIMEOUT = 20; // segundos por consulta

    /** Nomes dos tipos da PokéAPI em português (iguais aos da tabela "tipos"). */
    public const TIPOS = [
        'normal' => 'Normal',
        'fighting' => 'Lutador',
        'flying' => 'Voador',
        'poison' => 'Venenoso',
        'ground' => 'Terra',
        'rock' => 'Pedra',
        'bug' => 'Inseto',
        'ghost' => 'Fantasma',
        'steel' => 'Aço',
        'fire' => 'Fogo',
        'water' => 'Água',
        'grass' => 'Planta',
        'electric' => 'Elétrico',
        'psychic' => 'Psíquico',
        'ice' => 'Gelo',
        'dragon' => 'Dragão',
        'dark' => 'Sombrio',
        'fairy' => 'Fada',
    ];

    /** Busca um caminho da API (ex.: "pokemon/25") e devolve o JSON como array. */
    public function buscar(string $caminho): array
    {
        // contexto = opções da requisição: tempo limite e identificação
        $contexto = stream_context_create([
            'http' => [
                'timeout' => self::TIMEOUT,
                'header' => "User-Agent: MonstroBolso (trabalho escolar)\r\n",
            ],
        ]);

        // o @ esconde o aviso do PHP; o erro é tratado logo abaixo
        $corpo = @file_get_contents(self::URL . $caminho, false, $contexto);
        if ($corpo === false) {
            $motivo = error_get_last()['message'] ?? 'sem resposta';
            throw new RuntimeException("Falha ao consultar a PokéAPI ({$caminho}): {$motivo}");
        }

        $json = json_decode($corpo, true);
        if (!is_array($json)) {
            throw new RuntimeException("Resposta inválida da PokéAPI ({$caminho}).");
        }
        return $json;
    }

    /** Extrai o id do fim de uma URL da PokéAPI (".../pokemon-species/25/" -> 25). */
    public static function idDaUrl(string $url): int
    {
        return (int) basename(rtrim($url, '/'));
    }
}
