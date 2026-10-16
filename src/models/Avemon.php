<?php

namespace App\Models;

class Avemon {
    private ?int $idAvemon;
    private string $nome;
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
    private int $prioridade = 0;
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
    public function getPrioridade(): int { return $this->prioridade; }
    public function setPrioridade(int $prioridade): void { $this->prioridade = $prioridade; }
    public function getMovimentos(): array { return $this->movimentos; }

    public function adicionarMovimento(Movimento $movimento): void {
        if (count($this->movimentos) < 4) {
            $this->movimentos[] = $movimento;
        }
    }
}