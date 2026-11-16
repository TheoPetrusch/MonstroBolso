<?php

namespace App\Services;

use RuntimeException;

/**
 * Consumo da API EXTERNA PokéAPI (https://pokeapi.co) pelo back-end.
 * Faz as requisições em paralelo (curl_multi), em lotes, para a importação
 * de 151 Pokémon não demorar minutos.
 */
class PokeApiService
{
    private const URL = 'https://pokeapi.co/api/v2/';
    private const LOTE = 15;      // requisições simultâneas
    private const TIMEOUT = 20;   // segundos por requisição

    /** Nomes dos tipos da PokéAPI em português (iguais aos da tabela "tipos"). */
    public const TIPOS = [
        'normal' => 'Normal', 'fighting' => 'Lutador', 'flying' => 'Voador', 'poison' => 'Venenoso',
        'ground' => 'Terra', 'rock' => 'Pedra', 'bug' => 'Inseto', 'ghost' => 'Fantasma',
        'steel' => 'Aço', 'fire' => 'Fogo', 'water' => 'Água', 'grass' => 'Planta',
        'electric' => 'Elétrico', 'psychic' => 'Psíquico', 'ice' => 'Gelo', 'dragon' => 'Dragão',
        'dark' => 'Sombrio', 'fairy' => 'Fada',
    ];

    /**
     * Busca vários caminhos (ex.: "pokemon/25") e devolve o JSON de cada um,
     * mantendo as mesmas chaves do array recebido.
     * @param array<string|int, string> $caminhos
     */
    public function buscarVarios(array $caminhos): array
    {
        if (!function_exists('curl_multi_init')) {
            throw new RuntimeException('A extensão curl do PHP está desativada (ative extension=curl no php.ini).');
        }

        $resultado = [];
        foreach (array_chunk($caminhos, self::LOTE, true) as $lote) {
            $multi = curl_multi_init();
            $handles = [];

            foreach ($lote as $chave => $caminho) {
                $ch = curl_init(self::URL . $caminho);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_TIMEOUT        => self::TIMEOUT,
                    CURLOPT_USERAGENT      => 'MonstroBolso (trabalho escolar)',
                ]);
                curl_multi_add_handle($multi, $ch);
                $handles[$chave] = $ch;
            }

            do {
                $status = curl_multi_exec($multi, $rodando);
                if ($rodando) {
                    curl_multi_select($multi);
                }
            } while ($rodando && $status === CURLM_OK);

            foreach ($handles as $chave => $ch) {
                $corpo  = curl_multi_getcontent($ch);
                $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $erro   = curl_error($ch);
                curl_multi_remove_handle($multi, $ch);

                $json = is_string($corpo) ? json_decode($corpo, true) : null;
                if ($codigo !== 200 || !is_array($json)) {
                    $motivo = $erro !== '' ? $erro : "HTTP {$codigo}";
                    throw new RuntimeException("Falha ao consultar a PokéAPI ({$lote[$chave]}): {$motivo}");
                }
                $resultado[$chave] = $json;
            }
            curl_multi_close($multi);
        }
        return $resultado;
    }

    /** Extrai o id do fim de uma URL da PokéAPI (".../pokemon-species/25/" -> 25). */
    public static function idDaUrl(string $url): int
    {
        return (int) basename(rtrim($url, '/'));
    }
}
