<?php

namespace App\Exceptions;

use Exception;

class CredenciaisInvalidasException extends Exception
{
    public function __construct(string $mensagem = 'E-mail ou senha inválidos.')
    {
        parent::__construct($mensagem, 401);
    }
}
