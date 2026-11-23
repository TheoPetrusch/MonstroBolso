<?php

namespace App\Models\Tipos;

/** Tipo Voador: HERDA de Tipo e só informa o que muda nele. */
class Voador extends Tipo
{
    public function nome(): string
    {
        return 'Voador';
    }

    protected function superEfetivoContra(): array
    {
        return ['Lutador', 'Inseto', 'Planta'];
    }

    protected function poucoEfetivoContra(): array
    {
        return ['Pedra', 'Aço', 'Elétrico'];
    }
}
