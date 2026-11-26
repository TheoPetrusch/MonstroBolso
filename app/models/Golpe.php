<?php

namespace App\Models;

use App\Models\Tipos\Tipo;

/** Golpe que uma espécie pode usar em batalha. Imutável: só getters. */
class Golpe
{
    public function __construct(
        private int $id,
        private string $nome,
        private string $tipo,
        private int $poder,
        private int $precisao,
        private string $categoria // 'fisico' ou 'especial'
    ) {
    }

    /** Monta o objeto a partir de uma linha do banco (tabela golpes). */
    public static function fromRow(array $r): self
    {
        return new self(
            (int) $r['id'],
            $r['nome'],
            $r['tipo'],
            (int) $r['poder'],
            (int) $r['precisao'],
            $r['categoria']
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
    public function getPoder(): int
    {
        return $this->poder;
    }
    public function getPrecisao(): int
    {
        return $this->precisao;
    }
    public function getCategoria(): string
    {
        return $this->categoria;
    }

    /** O tipo do golpe como OBJETO: é ele que calcula a vantagem na batalha. */
    public function tipo(): Tipo
    {
        return Tipo::criar($this->tipo);
    }

    /** Versão em array, para virar JSON e ir para o JavaScript. */
    public function paraArray(): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'tipo' => $this->tipo,
            'poder' => $this->poder,
            'precisao' => $this->precisao,
            'categoria' => $this->categoria,
        ];
    }
}
