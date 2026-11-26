<?php

namespace App\Models\Tipos;

/** Tipo Voador: HERDA de Tipo e só informa o que muda nele. */
class Voador extends Tipo
{
    /** Implementa o método ABSTRATO nome() de Tipo (obrigatório na filha). */
    public function nome(): string
    {
        return 'Voador';
    }

    /** SOBRESCREVE o método da classe mãe: dano x2 contra estes tipos. */
    protected function superEfetivoContra(): array
    {
        return ['Lutador', 'Inseto', 'Planta'];
    }

    /** SOBRESCREVE: dano x0,5 contra estes tipos. */
    protected function poucoEfetivoContra(): array
    {
        return ['Pedra', 'Aço', 'Elétrico'];
    }
}
