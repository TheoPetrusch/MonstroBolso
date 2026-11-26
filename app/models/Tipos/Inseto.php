<?php

namespace App\Models\Tipos;

/** Tipo Inseto: HERDA de Tipo e só informa o que muda nele. */
class Inseto extends Tipo
{
    /** Implementa o método ABSTRATO nome() de Tipo (obrigatório na filha). */
    public function nome(): string
    {
        return 'Inseto';
    }

    /** SOBRESCREVE o método da classe mãe: dano x2 contra estes tipos. */
    protected function superEfetivoContra(): array
    {
        return ['Planta', 'Psíquico', 'Sombrio'];
    }

    /** SOBRESCREVE: dano x0,5 contra estes tipos. */
    protected function poucoEfetivoContra(): array
    {
        return ['Lutador', 'Voador', 'Venenoso', 'Fantasma', 'Aço', 'Fogo', 'Fada'];
    }
}
