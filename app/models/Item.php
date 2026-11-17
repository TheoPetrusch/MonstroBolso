<?php

namespace App\Models;

/** Item vendido no Poké Mart. Imutável: só getters. */
class Item
{
    public function __construct(
        private int $id,
        private string $nome,
        private string $categoria, // cura_hp, cura_status, reviver ou evolucao
        private string $descricao,
        private int $preco
    ) {}

    /** Linha vinda da view vw_loja. */
    public static function fromRow(array $r): self
    {
        return new self(
            (int) $r['id'],
            $r['nome'],
            $r['categoria'],
            $r['descricao'],
            (int) $r['preco']
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
    public function getCategoria(): string
    {
        return $this->categoria;
    }
    public function getPreco(): int
    {
        return $this->preco;
    }

    public function paraArray(): array
    {
        return [
            'id'        => $this->id,
            'nome'      => $this->nome,
            'categoria' => $this->categoria,
            'descricao' => $this->descricao,
            'preco'     => $this->preco,
        ];
    }
}
