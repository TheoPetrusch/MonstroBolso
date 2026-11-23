<?php

namespace App\Models;

use App\Models\Tipos\Tipo;

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
        private ?string $spriteUrl,
        private ?int $evoluiDeId = null,
        private bool $ignoraPreEvolucao = false
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
            $r['sprite_url'] ?? null,
            isset($r['evolui_de_id']) ? (int) $r['evolui_de_id'] : null,
            (bool) ($r['ignora_pre_evolucao'] ?? false)
        );
    }

    /**
     * Regra de negócio: espécie BÁSICA é a que não evolui de ninguém.
     * Só ela pode entrar no time; as evoluções vêm das pedras do Poké Mart.
     * Ex.: Charmander e Eevee são básicas; Charmeleon e Vaporeon não.
     * Exceção: se a pré-evolução é um bebê de uma geração posterior
     * (Pikachu <- Pichu), a espécie também é básica.
     */
    public function isBasica(): bool
    {
        return $this->evoluiDeId === null || $this->ignoraPreEvolucao;
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
    public function getAtaque(): int
    {
        return $this->ataque;
    }
    public function getDefesa(): int
    {
        return $this->defesa;
    }
    public function getSpAtk(): int
    {
        return $this->spAtk;
    }
    public function getSpDef(): int
    {
        return $this->spDef;
    }
    public function getSpriteUrl(): ?string
    {
        return $this->spriteUrl;
    }

    /** Força = soma dos 6 atributos (usada para sortear inimigos parecidos). */
    public function forca(): int
    {
        return $this->hp + $this->ataque + $this->defesa + $this->spAtk + $this->spDef + $this->velocidade;
    }

    /** O tipo como OBJETO (Fogo, Agua...), usado na batalha. */
    public function tipo(): Tipo
    {
        return Tipo::criar($this->tipo);
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
            'basica'     => $this->isBasica(),
        ];
    }
}
