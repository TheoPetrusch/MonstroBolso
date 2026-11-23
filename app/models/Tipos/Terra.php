<?php

namespace App\Models\Tipos;

/** Tipo Terra: HERDA de Tipo e só informa o que muda nele. */
class Terra extends Tipo
{
    public function nome(): string
    {
        return 'Terra';
    }

    protected function superEfetivoContra(): array
    {
        return ['Venenoso', 'Pedra', 'Aço', 'Fogo', 'Elétrico'];
    }

    protected function poucoEfetivoContra(): array
    {
        return ['Inseto', 'Planta'];
    }

    protected function semEfeitoContra(): array
    {
        return ['Voador'];
    }
}
