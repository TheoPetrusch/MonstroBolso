<?php

namespace App\Models\Tipos;

/** Tipo Terra: HERDA de Tipo e só informa o que muda nele. */
class Terra extends Tipo
{
    /** Implementa o método ABSTRATO nome() de Tipo (obrigatório na filha). */
    public function nome(): string
    {
        return 'Terra';
    }

    /** SOBRESCREVE o método da classe mãe: dano x2 contra estes tipos. */
    protected function superEfetivoContra(): array
    {
        return ['Venenoso', 'Pedra', 'Aço', 'Fogo', 'Elétrico'];
    }

    /** SOBRESCREVE: dano x0,5 contra estes tipos. */
    protected function poucoEfetivoContra(): array
    {
        return ['Inseto', 'Planta'];
    }

    /** SOBRESCREVE: dano x0 (não afeta) contra estes tipos. */
    protected function semEfeitoContra(): array
    {
        return ['Voador'];
    }
}
