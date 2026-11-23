<?php

namespace App\Models\Tipos;

/** Tipo Venenoso: HERDA de Tipo e só informa o que muda nele. */
class Venenoso extends Tipo
{
    public function nome(): string
    {
        return 'Venenoso';
    }

    protected function superEfetivoContra(): array
    {
        return ['Planta', 'Fada'];
    }

    protected function poucoEfetivoContra(): array
    {
        return ['Venenoso', 'Terra', 'Pedra', 'Fantasma'];
    }

    protected function semEfeitoContra(): array
    {
        return ['Aço'];
    }

    /** Golpes de Venenoso podem envenenar o alvo. */
    public function condicaoQueCausa(): ?string
    {
        return 'veneno';
    }
}
