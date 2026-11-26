<?php

namespace App\Models\Tipos;

/** Tipo Aço: HERDA de Tipo e só informa o que muda nele. */
class Aco extends Tipo
{
    /** Implementa o método ABSTRATO nome() de Tipo (obrigatório na filha). */
    public function nome(): string
    {
        return 'Aço';
    }

    /** SOBRESCREVE o método da classe mãe: dano x2 contra estes tipos. */
    protected function superEfetivoContra(): array
    {
        return ['Pedra', 'Gelo', 'Fada'];
    }

    /** SOBRESCREVE: dano x0,5 contra estes tipos. */
    protected function poucoEfetivoContra(): array
    {
        return ['Aço', 'Fogo', 'Água', 'Elétrico'];
    }
}
