<?php

namespace App\Models\Tipos;

/** Tipo Normal: HERDA de Tipo e só informa o que muda nele. */
class Normal extends Tipo
{
    /** Implementa o método ABSTRATO nome() de Tipo (obrigatório na filha). */
    public function nome(): string
    {
        return 'Normal';
    }

    /** SOBRESCREVE: dano x0,5 contra estes tipos. */
    protected function poucoEfetivoContra(): array
    {
        return ['Pedra', 'Aço'];
    }

    /** SOBRESCREVE: dano x0 (não afeta) contra estes tipos. */
    protected function semEfeitoContra(): array
    {
        return ['Fantasma'];
    }
}
