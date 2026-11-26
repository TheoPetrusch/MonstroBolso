<?php

namespace App\Models\Tipos;

/** Tipo Dragão: HERDA de Tipo e só informa o que muda nele. */
class Dragao extends Tipo
{
    /** Implementa o método ABSTRATO nome() de Tipo (obrigatório na filha). */
    public function nome(): string
    {
        return 'Dragão';
    }

    /** SOBRESCREVE o método da classe mãe: dano x2 contra estes tipos. */
    protected function superEfetivoContra(): array
    {
        return ['Dragão'];
    }

    /** SOBRESCREVE: dano x0,5 contra estes tipos. */
    protected function poucoEfetivoContra(): array
    {
        return ['Aço'];
    }

    /** SOBRESCREVE: dano x0 (não afeta) contra estes tipos. */
    protected function semEfeitoContra(): array
    {
        return ['Fada'];
    }
}
