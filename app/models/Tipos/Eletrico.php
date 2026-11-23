<?php

namespace App\Models\Tipos;

/** Tipo Elétrico: HERDA de Tipo e só informa o que muda nele. */
class Eletrico extends Tipo
{
    public function nome(): string
    {
        return 'Elétrico';
    }

    protected function superEfetivoContra(): array
    {
        return ['Voador', 'Água'];
    }

    protected function poucoEfetivoContra(): array
    {
        return ['Planta', 'Elétrico', 'Dragão'];
    }

    protected function semEfeitoContra(): array
    {
        return ['Terra'];
    }

    /** Golpes de Elétrico podem paralisar o alvo. */
    public function condicaoQueCausa(): ?string
    {
        return 'paralisado';
    }
}
