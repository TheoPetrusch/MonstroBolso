<?php

namespace App\Models\Tipos;

/** Tipo Água: HERDA de Tipo e só informa o que muda nele. */
class Agua extends Tipo
{
    /** Implementa o método ABSTRATO nome() de Tipo (obrigatório na filha). */
    public function nome(): string
    {
        return 'Água';
    }

    /** SOBRESCREVE o método da classe mãe: dano x2 contra estes tipos. */
    protected function superEfetivoContra(): array
    {
        return ['Terra', 'Pedra', 'Fogo'];
    }

    /** SOBRESCREVE: dano x0,5 contra estes tipos. */
    protected function poucoEfetivoContra(): array
    {
        return ['Água', 'Planta', 'Dragão'];
    }
}
