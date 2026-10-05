<?php

namespace App\Models;

class Item {
    private ?int $idItem;
    private string $nome;
    private string $efeito;
    private int $efeitoVal;
    private int $preco;

    public function __construct(
        ?int $idItem = null,
        string $nome = '',
        string $efeito = '',
        int $efeitoVal = 0,
        int $preco = 0
    ) {
        $this->idItem = $idItem;
        $this->nome = $nome;
        $this->efeito = $efeito;
        $this->efeitoVal = $efeitoVal;
        $this->preco = $preco;
    }

    public function getIdItem(): ?int { return $this->idItem; }
    public function getNome(): string { return $this->nome; }
    public function getEfeito(): string { return $this->efeito; }
    public function getEfeitoVal(): int { return $this->efeitoVal; }
    public function getPreco(): int { return $this->preco; }
}