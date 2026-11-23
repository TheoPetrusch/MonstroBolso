<?php

namespace App\Models\Tipos;

/** Tipo Pedra: HERDA de Tipo e só informa o que muda nele. */
class Pedra extends Tipo
{
    public function nome(): string
    {
        return 'Pedra';
    }

    protected function superEfetivoContra(): array
    {
        return ['Voador', 'Inseto', 'Fogo', 'Gelo'];
    }

    protected function poucoEfetivoContra(): array
    {
        return ['Lutador', 'Terra', 'Aço'];
    }
}
