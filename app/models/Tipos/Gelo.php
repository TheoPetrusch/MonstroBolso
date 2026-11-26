<?php

namespace App\Models\Tipos;

/** Tipo Gelo: HERDA de Tipo e só informa o que muda nele. */
class Gelo extends Tipo
{
    /** Implementa o método ABSTRATO nome() de Tipo (obrigatório na filha). */
    public function nome(): string
    {
        return 'Gelo';
    }

    /** SOBRESCREVE o método da classe mãe: dano x2 contra estes tipos. */
    protected function superEfetivoContra(): array
    {
        return ['Voador', 'Terra', 'Planta', 'Dragão'];
    }

    /** SOBRESCREVE: dano x0,5 contra estes tipos. */
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
