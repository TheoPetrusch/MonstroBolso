<?php

namespace App\Models\Tipos;

/** Tipo Fada: HERDA de Tipo e só informa o que muda nele. */
class Fada extends Tipo
{
    public function nome(): string
    {
        return 'Fada';
    }

    protected function superEfetivoContra(): array
    {
        return ['Lutador', 'Dragão', 'Sombrio'];
    }

    protected function poucoEfetivoContra(): array
    {
        return ['Venenoso', 'Aço', 'Fogo'];
    }
}
