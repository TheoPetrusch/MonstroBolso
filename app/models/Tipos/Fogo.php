<?php

namespace App\Models\Tipos;

/** Tipo Fogo: HERDA de Tipo e só informa o que muda nele. */
class Fogo extends Tipo
{
    /** Implementa o método ABSTRATO nome() de Tipo (obrigatório na filha). */
    public function nome(): string
    {
        return 'Fogo';
    }

    /** SOBRESCREVE o método da classe mãe: dano x2 contra estes tipos. */
    protected function superEfetivoContra(): array
    {
        return ['Inseto', 'Aço', 'Planta', 'Gelo'];
    }

    /** SOBRESCREVE: dano x0,5 contra estes tipos. */
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
