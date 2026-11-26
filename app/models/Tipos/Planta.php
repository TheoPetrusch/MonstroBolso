<?php

namespace App\Models\Tipos;

/** Tipo Planta: HERDA de Tipo e só informa o que muda nele. */
class Planta extends Tipo
{
    /** Implementa o método ABSTRATO nome() de Tipo (obrigatório na filha). */
    public function nome(): string
    {
        return 'Planta';
    }

    /** SOBRESCREVE o método da classe mãe: dano x2 contra estes tipos. */
    protected function superEfetivoContra(): array
    {
        return ['Terra', 'Pedra', 'Água'];
    }

    /** SOBRESCREVE: dano x0,5 contra estes tipos. */
    protected function poucoEfetivoContra(): array
    {
        return ['Voador', 'Venenoso', 'Inseto', 'Aço', 'Fogo', 'Planta', 'Dragão'];
    }
}
