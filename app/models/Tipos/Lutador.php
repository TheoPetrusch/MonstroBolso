<?php

namespace App\Models\Tipos;

/** Tipo Lutador: HERDA de Tipo e só informa o que muda nele. */
class Lutador extends Tipo
{
    /** Implementa o método ABSTRATO nome() de Tipo (obrigatório na filha). */
    public function nome(): string
    {
        return 'Lutador';
    }

    /** SOBRESCREVE o método da classe mãe: dano x2 contra estes tipos. */
    protected function superEfetivoContra(): array
    {
        return ['Normal', 'Pedra', 'Aço', 'Gelo', 'Sombrio'];
    }

    /** SOBRESCREVE: dano x0,5 contra estes tipos. */
    protected function poucoEfetivoContra(): array
    {
        return ['Voador', 'Venenoso', 'Inseto', 'Psíquico', 'Fada'];
    }

    /** SOBRESCREVE: dano x0 (não afeta) contra estes tipos. */
    protected function semEfeitoContra(): array
    {
        return ['Fantasma'];
    }
}
