<?php

namespace App\Models\Tipos;

/** Tipo Aço: HERDA de Tipo e só informa o que muda nele. */
class Aco extends Tipo
{
    public function nome(): string
    {
        return 'Aço';
    }

    protected function superEfetivoContra(): array
    {
        return ['Pedra', 'Gelo', 'Fada'];
    }

    protected function poucoEfetivoContra(): array
    {
        return ['Aço', 'Fogo', 'Água', 'Elétrico'];
    }
}
