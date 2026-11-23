<?php

namespace App\Models\Tipos;

/** Tipo Sombrio: HERDA de Tipo e só informa o que muda nele. */
class Sombrio extends Tipo
{
    public function nome(): string
    {
        return 'Sombrio';
    }

    protected function superEfetivoContra(): array
    {
        return ['Fantasma', 'Psíquico'];
    }

    protected function poucoEfetivoContra(): array
    {
        return ['Lutador', 'Sombrio', 'Fada'];
    }
}
