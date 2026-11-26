<?php

namespace App\Models\Tipos;

/** Tipo Sombrio: HERDA de Tipo e só informa o que muda nele. */
class Sombrio extends Tipo
{
    /** Implementa o método ABSTRATO nome() de Tipo (obrigatório na filha). */
    public function nome(): string
    {
        return 'Sombrio';
    }

    /** SOBRESCREVE o método da classe mãe: dano x2 contra estes tipos. */
    protected function superEfetivoContra(): array
    {
        return ['Fantasma', 'Psíquico'];
    }

    /** SOBRESCREVE: dano x0,5 contra estes tipos. */
    protected function poucoEfetivoContra(): array
    {
        return ['Lutador', 'Sombrio', 'Fada'];
    }
}
