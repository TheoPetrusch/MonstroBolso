<?php
namespace App\Models;

use App\Exceptions\TimeCheioException;

/**
 * Time AGREGA Pokémon: os Pokémon existem sem o time (agregação).
 * (Batalha, mais adiante, vai COMPOR seus turnos: composição.)
 */
class Time
{
    public const MAXIMO = 6;

    /** @var Pokemon[] */
    private array $pokemons = [];

    public function __construct(private int $usuarioId, array $pokemons = [])
    {
        foreach ($pokemons as $p) {
            $this->adicionar($p);
        }
    }

    public function adicionar(Pokemon $p): void
    {
        if ($this->estaCheio()) {
            throw new TimeCheioException(self::MAXIMO);
        }
        $this->pokemons[] = $p;
    }

    public function remover(int $pokemonId): void
    {
        $this->pokemons = array_values(array_filter(
            $this->pokemons,
            fn(Pokemon $p) => $p->getId() !== $pokemonId
        ));
    }

    public function estaCheio(): bool { return count($this->pokemons) >= self::MAXIMO; }
    public function getUsuarioId(): int { return $this->usuarioId; }
    /** @return Pokemon[] */
    public function getPokemons(): array { return $this->pokemons; }

    public function paraArray(): array
    {
        return [
            'maximo'   => self::MAXIMO,
            'pokemons' => array_map(fn(Pokemon $p) => $p->paraArray(), $this->pokemons),
        ];
    }
}
