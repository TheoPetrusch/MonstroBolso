<?php

namespace App\Models\Tipos;

/** Tipo Água: HERDA de Tipo e só informa o que muda nele. */
class Agua extends Tipo
{
    public function nome(): string
    {
        return 'Água';
    }

    protected function superEfetivoContra(): array
    {
        return ['Terra', 'Pedra', 'Fogo'];
    }

    protected function poucoEfetivoContra(): array
    {
        return ['Água', 'Planta', 'Dragão'];
    }
}
