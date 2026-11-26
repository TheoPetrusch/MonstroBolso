<?php

namespace App\Models\Tipos;

/** Tipo Fantasma: HERDA de Tipo e só informa o que muda nele. */
class Fantasma extends Tipo
{
    /** Implementa o método ABSTRATO nome() de Tipo (obrigatório na filha). */
    public function nome(): string
    {
        return 'Fantasma';
    }

    /** SOBRESCREVE o método da classe mãe: dano x2 contra estes tipos. */
    protected function superEfetivoContra(): array
    {
        return ['Fantasma', 'Psíquico'];
    }

    /** SOBRESCREVE: dano x0,5 contra estes tipos. */
    protected function poucoEfetivoContra(): array
    {
        return ['Sombrio'];
    }

    /** SOBRESCREVE: dano x0 (não afeta) contra estes tipos. */
    protected function semEfeitoContra(): array
    {
        return ['Normal'];
    }
}
