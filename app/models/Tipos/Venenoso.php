<?php

namespace App\Models\Tipos;

/** Tipo Venenoso: HERDA de Tipo e só informa o que muda nele. */
class Venenoso extends Tipo
{
    /** Implementa o método ABSTRATO nome() de Tipo (obrigatório na filha). */
    public function nome(): string
    {
        return 'Venenoso';
    }

    /** SOBRESCREVE o método da classe mãe: dano x2 contra estes tipos. */
    protected function superEfetivoContra(): array
    {
        return ['Planta', 'Fada'];
    }

    /** SOBRESCREVE: dano x0,5 contra estes tipos. */
    protected function poucoEfetivoContra(): array
    {
        return ['Venenoso', 'Terra', 'Pedra', 'Fantasma'];
    }

    /** SOBRESCREVE: dano x0 (não afeta) contra estes tipos. */
    protected function semEfeitoContra(): array
    {
        return ['Aço'];
    }

    /** Golpes de Venenoso podem envenenar o alvo. */
    public function condicaoQueCausa(): ?string
    {
        return 'veneno';
    }
}
