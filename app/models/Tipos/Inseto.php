<?php

namespace App\Models\Tipos;

/** Tipo Inseto: HERDA de Tipo e só informa o que muda nele. */
class Inseto extends Tipo
{
    public function nome(): string
    {
        return 'Inseto';
    }

    protected function superEfetivoContra(): array
    {
        return ['Planta', 'Psíquico', 'Sombrio'];
    }

    protected function poucoEfetivoContra(): array
    {
        return ['Lutador', 'Voador', 'Venenoso', 'Fantasma', 'Aço', 'Fogo', 'Fada'];
    }
}
