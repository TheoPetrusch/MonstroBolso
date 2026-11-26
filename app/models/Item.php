<?php

namespace App\Models;

/**
 * Item vendido no Poké Mart. Imutável: só getters (ENCAPSULAMENTO: os
 * atributos são private e ninguém de fora consegue alterar).
 * O efeito de cada item é aplicado em Pokemon::usarItem(), conforme a categoria.
 */
class Item
{
    public function __construct(
        private int $id,
        private string $nome,
        private string $categoria, // cura_hp, cura_status, reviver ou evolucao
        private string $descricao,
        private int $preco,
        // efeitos (colunas da tabela itens); a vw_loja não traz, então têm padrão
        private int $curaHp = 0,            // cura fixa (Potion = 20)
        private int $curaPercentual = 0,    // 0..100 (Max Potion = 100, Revive = 50)
        private ?string $statusCurado = null, // ex.: 'veneno' (Antidote)
        private bool $curaTodosStatus = false // Full Heal / Full Restore
    ) {}

    /** Linha vinda da view vw_loja ou da tabela itens. */
    public static function fromRow(array $r): self
    {
        return new self(
            (int) $r['id'],
            $r['nome'],
            $r['categoria'],
            $r['descricao'],
            (int) $r['preco'],
            (int) ($r['cura_hp'] ?? 0),
            (int) ($r['cura_percentual'] ?? 0),
            $r['status_curado'] ?? null,
            (bool) ($r['cura_todos_status'] ?? false)
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
    public function getCuraHp(): int
    {
        return $this->curaHp;
    }
    public function getCuraPercentual(): int
    {
        return $this->curaPercentual;
    }
    public function getStatusCurado(): ?string
    {
        return $this->statusCurado;
    }
    public function curaTodosStatus(): bool
    {
        return $this->curaTodosStatus;
    }

    /** Só o que a loja mostra (os efeitos ficam no servidor). */
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
