<?php

namespace App\Models;

/**
 * Espécie = dados base de um Pokémon (stats da PokéAPI).
 * Imutável: só getters.
 */
class Especie
{
    public function __construct(
        private int $id,
        private int $pokeapiId,
        private string $nome,
        private string $tipo,
        private int $hp,
        private int $ataque,
        private int $defesa,
        private int $spAtk,
        private int $spDef,
        private int $velocidade,
        private ?string $spriteUrl
    ) {}

    public static function fromRow(array $r): self
    {
        return new self(
            (int) $r['id'],
            (int) $r['pokeapi_id'],
            $r['nome'],
            $r['tipo'],
            (int) $r['hp'],
            (int) $r['ataque'],
            (int) $r['defesa'],
            (int) $r['sp_atk'],
            (int) $r['sp_def'],
            (int) $r['velocidade'],
            $r['sprite_url'] ?? null
        );
    }

    public function getId(): int
    {
        return $this->id;
    }
    public function getPokeapiId(): int
    {
        return $this->pokeapiId;
    }
    public function getNome(): string
    {
        return $this->nome;
    }
    public function getTipo(): string
    {
        return $this->tipo;
    }
    public function getHp(): int
    {
        return $this->hp;
    }
    public function getVelocidade(): int
    {
        return $this->velocidade;
    }

    public function paraArray(): array
    {
        return [
            'id'         => $this->id,
            'pokeapi_id' => $this->pokeapiId,
            'nome'       => $this->nome,
            'tipo'       => $this->tipo,
            'hp'         => $this->hp,
            'ataque'     => $this->ataque,
            'defesa'     => $this->defesa,
            'sp_atk'     => $this->spAtk,
            'sp_def'     => $this->spDef,
            'velocidade' => $this->velocidade,
            'sprite_url' => $this->spriteUrl,
        ];
    }
}
