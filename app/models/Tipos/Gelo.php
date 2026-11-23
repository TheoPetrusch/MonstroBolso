<?php

namespace App\Models\Tipos;

/** Tipo Gelo: HERDA de Tipo e só informa o que muda nele. */
class Gelo extends Tipo
{
    public function nome(): string
    {
        return 'Gelo';
    }

    protected function superEfetivoContra(): array
    {
        return ['Voador', 'Terra', 'Planta', 'Dragão'];
    }

    protected function poucoEfetivoContra(): array
    {
        return ['Aço', 'Fogo', 'Água', 'Gelo'];
    }

    /** Golpes de Gelo podem congelar o alvo. */
    public function condicaoQueCausa(): ?string
    {
        return 'congelado';
    }
}
