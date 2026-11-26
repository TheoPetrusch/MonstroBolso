<?php

namespace App\Models\Tipos;

/** Tipo Pedra: HERDA de Tipo e só informa o que muda nele. */
class Pedra extends Tipo
{
    /** Implementa o método ABSTRATO nome() de Tipo (obrigatório na filha). */
    public function nome(): string
    {
        return 'Pedra';
    }

    /** SOBRESCREVE o método da classe mãe: dano x2 contra estes tipos. */
    protected function superEfetivoContra(): array
    {
        return ['Voador', 'Inseto', 'Fogo', 'Gelo'];
    }

    /** SOBRESCREVE: dano x0,5 contra estes tipos. */
    protected function poucoEfetivoContra(): array
    {
        return ['Lutador', 'Terra', 'Aço'];
    }
}
