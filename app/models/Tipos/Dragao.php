<?php

namespace App\Models\Tipos;

/** Tipo Dragão: HERDA de Tipo e só informa o que muda nele. */
class Dragao extends Tipo
{
    public function nome(): string
    {
        return 'Dragão';
    }

    protected function superEfetivoContra(): array
    {
        return ['Dragão'];
    }

    protected function poucoEfetivoContra(): array
    {
        return ['Aço'];
    }

    protected function semEfeitoContra(): array
    {
        return ['Fada'];
    }
}
