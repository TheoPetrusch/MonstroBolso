<?php

namespace App\Models\Tipos;

/** Tipo Normal: HERDA de Tipo e só informa o que muda nele. */
class Normal extends Tipo
{
    public function nome(): string
    {
        return 'Normal';
    }

    protected function poucoEfetivoContra(): array
    {
        return ['Pedra', 'Aço'];
    }

    protected function semEfeitoContra(): array
    {
        return ['Fantasma'];
    }
}
