<?php
namespace App\Exceptions;

use Exception;

/** Exceção customizada: tentativa de passar de 6 Pokémon no time. */
class TimeCheioException extends Exception
{
    public function __construct(int $limite = 6)
    {
        parent::__construct("O time já tem o máximo de {$limite} Pokémon.", 409);
    }
}
