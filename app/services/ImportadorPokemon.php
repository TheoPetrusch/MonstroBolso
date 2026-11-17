<?php

namespace App\Services;

use App\Core\Database;
use App\Models\EspecieRepository;
use App\Models\GolpeRepository;
use App\Models\SeletorDeGolpes;
use PDO;
use RuntimeException;

/**
 * Importa espécies da PokéAPI para o banco:
 *   1) baixa tudo primeiro (pokemon/{id} e pokemon-species/{id});
 *   2) grava tudo numa única TRANSAÇÃO: espécies, golpes e evoluções.
 * Se a internet cair no meio, nada é gravado pela metade.
 */
class ImportadorPokemon
{
    private PDO $pdo;
    private PokeApiService $api;
    private EspecieRepository $especies;
    private GolpeRepository $golpes;

    public function __construct(?PokeApiService $api = null, ?PDO $pdo = null)
    {
        $this->pdo      = $pdo ?? Database::getConnection();
        $this->api      = $api ?? new PokeApiService();
        $this->especies = new EspecieRepository($this->pdo);
        $this->golpes   = new GolpeRepository($this->pdo);
    }

    /** @return array{importadas:int, evolucoes:int, sem_evolucao:string[]} */
    public function importar(int $de, int $ate): array
    {
        // ---------- 1) baixa os dados ----------
        $caminhos = [];
        foreach (range($de, $ate) as $id) {
            $caminhos["p{$id}"] = "pokemon/{$id}";
            $caminhos["s{$id}"] = "pokemon-species/{$id}";
        }
        $json = $this->api->buscarVarios($caminhos);

        $dados = [];
        foreach (range($de, $ate) as $id) {
            $dados[$id] = self::montarDados($json["p{$id}"], $json["s{$id}"]);
        }

        // quem evolui para quem, só entre as espécies importadas agora
        // (ex.: Pikachu vem de Pichu, da 2ª geração, que não está na lista)
        $filhos = [];
        foreach ($dados as $id => &$d) {
            if ($d['evolui_de'] !== null && !isset($dados[$d['evolui_de']])) {
                $d['evolui_de'] = null;
            }
            if ($d['evolui_de'] !== null) {
                $filhos[$d['evolui_de']][] = $id;
            }
        }
        unset($d);

        // ---------- 2) grava tudo numa transação ----------
        $seletor = new SeletorDeGolpes($this->golpes->listar());
        $semEvolucao = [];
        $evolucoes = 0;

        $this->pdo->beginTransaction();
        try {
            foreach ($dados as $id => $d) {
                $especieId = $this->especies->salvarDaApi($d);

                $primeiroEstagio = $d['evolui_de'] === null && !empty($filhos[$id]);
                $this->golpes->definirDaEspecie($especieId, $seletor->escolher(
                    $d['tipo'],
                    $d['tipo2'],
                    $d['ataque'],
                    $d['sp_atk'],
                    $d['velocidade'],
                    $primeiroEstagio
                ));
            }

            foreach ($filhos as $paiId => $lista) {
                if (count($lista) === 1) {
                    $this->especies->definirEvolucao($paiId, $lista[0]);
                    $evolucoes++;
                } else {
                    // ex.: Eevee tem 3 evoluções e o schema guarda só uma
                    $semEvolucao[] = $dados[$paiId]['nome'];
                }
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return [
            'importadas'   => count($dados),
            'evolucoes'    => $evolucoes,
            'sem_evolucao' => $semEvolucao,
        ];
    }

    /**
     * Converte as respostas da PokéAPI no array que o repository grava.
     * Público e estático para poder ser testado sem internet nem banco.
     */
    public static function montarDados(array $pokemon, array $especie): array
    {
        $stats = [];
        foreach ($pokemon['stats'] as $s) {
            $stats[$s['stat']['name']] = (int) $s['base_stat'];
        }

        $tipos = $pokemon['types'];
        usort($tipos, fn($a, $b) => $a['slot'] <=> $b['slot']);
        $tipos = array_map(function ($t) {
            $nome = $t['type']['name'];
            if (!isset(PokeApiService::TIPOS[$nome])) {
                throw new RuntimeException("Tipo desconhecido na PokéAPI: {$nome}");
            }
            return PokeApiService::TIPOS[$nome];
        }, $tipos);

        $nome = ucfirst($pokemon['name']);
        foreach ($especie['names'] ?? [] as $n) {
            if ($n['language']['name'] === 'en') {
                $nome = $n['name'];
                break;
            }
        }

        $pai = $especie['evolves_from_species']['url'] ?? null;

        return [
            'pokeapi_id' => (int) $pokemon['id'],
            'nome'       => mb_substr($nome, 0, 40),
            'tipo'       => $tipos[0],
            'tipo2'      => $tipos[1] ?? null,
            'hp'         => $stats['hp'],
            'ataque'     => $stats['attack'],
            'defesa'     => $stats['defense'],
            'sp_atk'     => $stats['special-attack'],
            'sp_def'     => $stats['special-defense'],
            'velocidade' => $stats['speed'],
            'sprite_url' => $pokemon['sprites']['front_default'] ?? null,
            'evolui_de'  => $pai ? PokeApiService::idDaUrl($pai) : null,
        ];
    }
}
