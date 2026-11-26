<?php

namespace App\Exceptions;

use Exception;

/** Exceção customizada: tentativa de passar de 6 Pokémon no time. */
class TimeCheioException extends Exception
{
    /** Código 409 (conflito): o TimeController usa ele como status HTTP. */
    public function __construct(int $limite = 6)
    {
        parent::__construct("O time já tem o máximo de {$limite} Pokémon.", 409);
    }
}
