<?php

namespace App\Models\Tipos;

/** Tipo Psíquico: HERDA de Tipo e só informa o que muda nele. */
class Psiquico extends Tipo
{
    public function nome(): string
    {
        return 'Psíquico';
    }

    protected function superEfetivoContra(): array
    {
        return ['Lutador', 'Venenoso'];
    }

    protected function poucoEfetivoContra(): array
    {
        return ['Aço', 'Psíquico'];
    }

    protected function semEfeitoContra(): array
    {
        return ['Sombrio'];
    }

    /** Golpes de Psíquico podem fazer dormir o alvo. */
    public function condicaoQueCausa(): ?string
    {
        return 'dormindo';
    }
}
