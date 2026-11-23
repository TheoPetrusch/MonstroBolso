<?php

namespace App\Models;

/** Líder de ginásio (tabela lideres). Imutável: só getters. */
class Lider
{
    public function __construct(
        private int $id,
        private int $ordem,        // 1 = mais fraco ... 8 = mais forte
        private string $nome,
        private string $tipo,      // tipo em que é especialista
        private int $quantidade,   // Pokémon no time
        private int $forca,        // força média dos Pokémon (soma dos 6 atributos)
        private int $premio,
        private string $insignia
    ) {}

    public static function fromRow(array $r): self
    {
        return new self(
            (int) $r['id'],
            (int) $r['ordem'],
            $r['nome'],
            $r['tipo'],
            (int) $r['quantidade'],
            (int) $r['forca'],
            (int) $r['premio'],
            $r['insignia']
        );
    }

    public function getId(): int
    {
        return $this->id;
    }
    public function getNome(): string
    {
        return $this->nome;
    }
    public function getTipo(): string
    {
        return $this->tipo;
    }
    public function getQuantidade(): int
    {
        return $this->quantidade;
    }
    public function getForca(): int
    {
        return $this->forca;
    }
    public function getPremio(): int
    {
        return $this->premio;
    }

    public function paraArray(): array
    {
        return [
            'id'         => $this->id,
            'ordem'      => $this->ordem,
            'nome'       => $this->nome,
            'tipo'       => $this->tipo,
            'quantidade' => $this->quantidade,
            'premio'     => $this->premio,
            'insignia'   => $this->insignia,
        ];
    }
}
