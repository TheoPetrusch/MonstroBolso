<?php

namespace App\Exceptions;

use InvalidArgumentException;

/**
 * Exceção customizada: o time já tem um Pokémon da mesma linha evolutiva
 * (ex.: tentar colocar um Charmeleon num time que já tem um Charmander).
 * Herda de InvalidArgumentException, então o controller já responde 422.
 */
class LinhaRepetidaException extends InvalidArgumentException
{
    public function __construct(string $noTime, string $nova)
    {
        parent::__construct(
            "Seu time já tem {$noTime}, que é da mesma linha evolutiva de {$nova}. "
            . 'Não pode haver Pokémon repetido nem evoluções do mesmo Pokémon no time '
            . '(exceção: as evoluções do Eevee podem ficar juntas).'
        );
    }
}
