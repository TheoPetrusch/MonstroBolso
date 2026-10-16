<?php

namespace App\Models;

class Movimento {
    private ?int $idMovimento;
    private string $nome;
    private string $descricao;
    private string $efeito;
    private int $efeitoVal;
    private int $prioridade;

    public function __construct(
        ?int $idMovimento = null,
        string $nome = '',
        string $descricao = '',
        string $efeito = '',
        int $efeitoVal = 0,
        int $prioridade = 0
    ) {
        $this->idMovimento = $idMovimento;
        $this->nome = $nome;
        $this->descricao = $descricao;
        $this->efeito = $efeito;
        $this->efeitoVal = $efeitoVal;
        $this->prioridade = $prioridade;
    }

    public function getIdMovimento(): ?int { return $this->idMovimento; }
    public function getNome(): string { return $this->nome; }
    public function getEfeito(): string { return $this->efeito; }
    public function getEfeitoVal(): int { return $this->efeitoVal; }
    public function getPrioridade(): int { return $this->prioridade; }
}