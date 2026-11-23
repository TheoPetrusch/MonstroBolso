<?php

namespace App\Models\Tipos;

/** Tipo Fogo: HERDA de Tipo e só informa o que muda nele. */
class Fogo extends Tipo
{
    public function nome(): string
    {
        return 'Fogo';
    }

    protected function superEfetivoContra(): array
    {
        return ['Inseto', 'Aço', 'Planta', 'Gelo'];
    }

    protected function poucoEfetivoContra(): array
    {
        return ['Pedra', 'Fogo', 'Água', 'Dragão'];
    }

    /** Golpes de Fogo podem queimar o alvo. */
    public function condicaoQueCausa(): ?string
    {
        return 'queimado';
    }
}
