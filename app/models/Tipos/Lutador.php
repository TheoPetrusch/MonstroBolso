<?php

namespace App\Models\Tipos;

/** Tipo Lutador: HERDA de Tipo e só informa o que muda nele. */
class Lutador extends Tipo
{
    public function nome(): string
    {
        return 'Lutador';
    }

    protected function superEfetivoContra(): array
    {
        return ['Normal', 'Pedra', 'Aço', 'Gelo', 'Sombrio'];
    }

    protected function poucoEfetivoContra(): array
    {
        return ['Voador', 'Venenoso', 'Inseto', 'Psíquico', 'Fada'];
    }

    protected function semEfeitoContra(): array
    {
        return ['Fantasma'];
    }
}
