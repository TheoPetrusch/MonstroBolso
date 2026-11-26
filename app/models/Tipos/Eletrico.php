<?php

namespace App\Models\Tipos;

/** Tipo Elétrico: HERDA de Tipo e só informa o que muda nele. */
class Eletrico extends Tipo
{
    /** Implementa o método ABSTRATO nome() de Tipo (obrigatório na filha). */
    public function nome(): string
    {
        return 'Elétrico';
    }

    /** SOBRESCREVE o método da classe mãe: dano x2 contra estes tipos. */
    protected function superEfetivoContra(): array
    {
        return ['Voador', 'Água'];
    }

    /** SOBRESCREVE: dano x0,5 contra estes tipos. */
    protected function poucoEfetivoContra(): array
    {
        return ['Planta', 'Elétrico', 'Dragão'];
    }

    /** SOBRESCREVE: dano x0 (não afeta) contra estes tipos. */
    protected function semEfeitoContra(): array
    {
        return ['Terra'];
    }

    /** Golpes de Elétrico podem paralisar o alvo. */
    public function condicaoQueCausa(): ?string
    {
        return 'paralisado';
    }
}
