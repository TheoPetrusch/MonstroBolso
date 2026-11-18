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
 * Dá para importar cada geração separadamente, em qualquer ordem.
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

    /**
     * Gerações da Pokédex nacional (número da geração => [primeiro, último]).
     * A 9ª termina no Pecharunt (#1025).
     */
    public const GERACOES = [
        1 => [1, 151],
        2 => [152, 251],
        3 => [252, 386],
        4 => [387, 493],
        5 => [494, 649],
        6 => [650, 721],
        7 => [722, 809],
        8 => [810, 905],
        9 => [906, 1025],
    ];

    /** Importa uma geração inteira (1 a 9). */
    public function importarGeracao(int $geracao): array
    {
        if (!isset(self::GERACOES[$geracao])) {
            throw new \InvalidArgumentException('Geração inválida (1 a 9).');
        }
        [$de, $ate] = self::GERACOES[$geracao];
        return $this->importar($de, $ate);
    }

    /** @return array{importadas:int, evolucoes:int, pre_evolucoes:string[]} */
    public function importar(int $de, int $ate): array
    {
        // ---------- 1) baixa os dados (2 consultas por Pokémon) ----------
        $dados = [];
        foreach (range($de, $ate) as $id) {
            $dados[$id] = $this->baixar($id);
        }

        // Pré-evoluções de outra geração vêm junto, se ainda não estão no banco
        // (ex.: importar a 1ª traz o Pichu, da 2ª, para o Pikachu ter de onde
        // evoluir). Repete até não faltar ninguém, porque a pré-evolução pode
        // ter outra antes dela (Magmortar <- Magmar <- Magby).
        $noBanco = array_flip($this->especies->pokeapiIds());
        $preEvolucoes = [];
        while ($faltando = self::paisFaltando($dados, $noBanco)) {
            foreach ($faltando as $id) {
                $dados[$id] = $this->baixar($id);
                $preEvolucoes[] = $dados[$id]['nome'];
            }
        }

        // ---------- 2) grava tudo numa transação ----------
        $seletor = new SeletorDeGolpes($this->golpes->listar());

        $this->pdo->beginTransaction();
        try {
            // a) espécies (guardam o pokeapi_id do pai, mesmo de outra geração)
            $ids = [];
            foreach ($dados as $pid => $d) {
                $ids[$pid] = $this->especies->salvarDaApi($d);
            }

            // b) liga as evoluções do banco todo: a ordem das gerações não importa
            $evolucoes = $this->especies->ligarEvolucoes();

            // c) golpes: 1º estágio = não vem de ninguém e tem evolução no banco
            $temEvolucao = array_flip($this->especies->pokeapiIdsComEvolucao());
            foreach ($dados as $pid => $d) {
                $primeiroEstagio = $d['evolui_de'] === null && isset($temEvolucao[$pid]);
                $this->golpes->definirDaEspecie($ids[$pid], $seletor->escolher(
                    $d['tipo'],
                    $d['tipo2'],
                    $d['ataque'],
                    $d['sp_atk'],
                    $d['velocidade'],
                    $primeiroEstagio
                ));
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return [
            'importadas'    => count($dados),
            'evolucoes'     => $evolucoes,
            'pre_evolucoes' => $preEvolucoes,
        ];
    }

    /** Baixa uma espécie da PokéAPI (2 consultas) e converte para o formato do banco. */
    private function baixar(int $id): array
    {
        $pokemon = $this->api->buscar("pokemon/{$id}");          // stats, tipos, sprite
        $especie = $this->api->buscar("pokemon-species/{$id}");  // nome e pré-evolução
        return self::montarDados($pokemon, $especie);
    }

    /**
     * pokeapi_id dos pais que não estão nem na importação atual nem no banco.
     * Público e estático para poder ser testado sem internet nem banco.
     * @param array<int, array> $dados   espécies baixadas, pelo pokeapi_id
     * @param array<int, mixed> $noBanco pokeapi_id => qualquer coisa
     * @return int[]
     */
    public static function paisFaltando(array $dados, array $noBanco): array
    {
        $faltando = [];
        foreach ($dados as $d) {
            $pai = $d['evolui_de'];
            if ($pai !== null && !isset($dados[$pai]) && !isset($noBanco[$pai])) {
                $faltando[$pai] = $pai; // chave evita repetir (Eevee teria 3 filhos pedindo)
            }
        }
        return array_values($faltando);
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
