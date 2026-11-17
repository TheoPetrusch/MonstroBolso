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
        // ---------- 1) baixa os dados (2 consultas por Pokémon) ----------
        $dados = [];
        foreach (range($de, $ate) as $id) {
            $pokemon = $this->api->buscar("pokemon/{$id}");          // stats, tipos, sprite
            $especie = $this->api->buscar("pokemon-species/{$id}");  // nome e pré-evolução
            $dados[$id] = self::montarDados($pokemon, $especie);
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
                // todo filho guarda o pai, mesmo quando são vários (Eevee):
                // é isso que tira Vaporeon, Jolteon e Flareon da lista de básicos
                foreach ($lista as $filhoId) {
                    $this->especies->definirPreEvolucao($paiId, $filhoId);
                }

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

        // O jogo usa um tipo só (o 1º da PokéAPI). Aves como Pidgey são
        // Normal/Voador e ficariam "Normal": aqui elas viram Voador,
        // e Normal passa a ser o 2º tipo (usado só para escolher golpes).
        if ($tipos[0] === 'Normal' && ($tipos[1] ?? null) === 'Voador') {
            $tipos = ['Voador', 'Normal'];
        }

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
