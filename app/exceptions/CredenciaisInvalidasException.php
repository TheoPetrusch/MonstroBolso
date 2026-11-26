<?php

namespace App\Exceptions;

use Exception;

/**
 * Exceção CUSTOMIZADA: login com e-mail ou senha errados.
 * Herda de Exception; o código 401 (não autorizado) vira o status HTTP
 * da resposta no AuthController.
 */
class CredenciaisInvalidasException extends Exception
{
    public function __construct(string $mensagem = 'E-mail ou senha inválidos.')
    {
        parent::__construct($mensagem, 401);
    }
}
