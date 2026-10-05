<?php

namespace App\Models;

class Usuario {
    private ?int $idUsuario;
    private string $nome;
    private string $senha;
    private int $pokedollars;

    public function __construct(
        ?int $idUsuario = null,
        string $nome = '',
        string $senha = '',
        int $pokedollars = 0
    ) {
        $this->idUsuario = $idUsuario;
        $this->nome = $nome;
        $this->senha = $senha;
        $this->pokedollars = $pokedollars;
    }

    public function getIdUsuario(): ?int { return $this->idUsuario; }
    public function getNome(): string { return $this->nome; }
    public function getPokedollars(): int { return $this->pokedollars; }
    public function setPokedollars(int $pokedollars): void { $this->pokedollars = $pokedollars; }
}