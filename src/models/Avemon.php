<?php

namespace App\Models;

class Avemon {
    private ?int $idAvemon;
    private string $nome;

    private ?string $caminhoImagem;
    private ?string $descricao;
    private string $tipo1;
    private ?string $tipo2;
    private int $hpMax;
    private int $velocidade;
    private int $ataque;
    private int $ataqueEspecial;
    private int $defesa;
    private int $defesaEspecial;

    // Atributos de controle em memória
    private int $hpAtual;
    private string $status;
    private array $movimentos = [];

    public function __construct(
        ?int $idAvemon = null,
        string $nome = '',
        int $hpMax = 0,
        int $velocidade = 0,
        string $tipo1 = 'NOR',
        ?string $tipo2 = null
    ) {
        $this->idAvemon = $idAvemon;
        $this->nome = $nome;
        $this->hpMax = $hpMax;
        $this->hpAtual = $hpMax;
        $this->velocidade = $velocidade;
        $this->tipo1 = $tipo1;
        $this->tipo2 = $tipo2;
        $this->status = 'normal';
    }

    public function getNome(): string { return $this->nome; }
    public function getHpMax(): int { return $this->hpMax; }
    public function getHpAtual(): int { return $this->hpAtual; }
    public function getVelocidade(): int { return $this->velocidade; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): void { $this->status = $status; }
    public function getMovimentos(): array { return $this->movimentos; }

    public function adicionarMovimento(Movimento $movimento): void {
        if (count($this->movimentos) < 4) {
            $this->movimentos[] = $movimento;
        }
    }

    public static function importPokeApi(int $idPokemon, array $dadosPersonalizados, \PDO $pdo): bool{
        $url = "https://pokeapi.com/api/v2/pokemon/{$idPokemon}";
        
        $options = [
            "http" => [
                "header" => "User-Agent: PHP-Game_Client\r\n"
            ]
        ];
        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        
        if ($response === false) {
            return false;
        }

        $data = json_decode($response, true);

        $stats = [];
        foreach ($data['stats'] as $s) {
            $stats[$s['stat']['name']] = $s['base_stat'];
        }

        $sql = "INSERT INTO avemon
                (nome, caminhoImagem, descricao, tipo1, tipo2, hp, velocidade, ataque, ataqueEspecial, defesa, defesaEspecial)
                VALUES (:nome, :caminhoImagem, :descricao, :tipo1, :tipo2, :hp, :velocidade, :ataque, :ataqueEspecial, :defesa, :defesaEspecial)";
        
        $stmt = $pdo->prepare($sql);

        return $stmt->execute([
            ':nome' => $dadosPersonalizados['nome'],
            ':caminhoImagem' => $dadosPersonalizados['caminhoImagem'],
            ':descricao' => $dadosPersonalizados['descricao'],
            ':tipo1' => $dadosPersonalizados['tipo1'],
            ':tipo2' => $dadosPersonalizados['tipo2'] ?? null,
            ':hp' => $stats['hp'] ?? 50,
            ':velocidade' => $stats['speed'] ?? 50,
            ':ataque' => $stats['attack'] ?? 50,
            ':ataqueEspecial' => $stats['special-attack'] ?? 50,
            ':defesa' => $stats['defense'] ?? 50,
            ':defesaEspecial' => $stats['special-defense'] ?? 50
        ]);
    }
}