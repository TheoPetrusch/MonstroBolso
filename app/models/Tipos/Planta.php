<?php

namespace App\Models\Tipos;

/** Tipo Planta: HERDA de Tipo e só informa o que muda nele. */
class Planta extends Tipo
{
    public function nome(): string
    {
        return 'Planta';
    }

    protected function superEfetivoContra(): array
    {
        return ['Terra', 'Pedra', 'Água'];
    }

    protected function poucoEfetivoContra(): array
    {
        return ['Voador', 'Venenoso', 'Inseto', 'Aço', 'Fogo', 'Planta', 'Dragão'];
    }
}
