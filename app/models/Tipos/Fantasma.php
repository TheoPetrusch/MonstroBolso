<?php

namespace App\Models\Tipos;

/** Tipo Fantasma: HERDA de Tipo e só informa o que muda nele. */
class Fantasma extends Tipo
{
    public function nome(): string
    {
        return 'Fantasma';
    }

    protected function superEfetivoContra(): array
    {
        return ['Fantasma', 'Psíquico'];
    }

    protected function poucoEfetivoContra(): array
    {
        return ['Sombrio'];
    }

    protected function semEfeitoContra(): array
    {
        return ['Normal'];
    }
}
