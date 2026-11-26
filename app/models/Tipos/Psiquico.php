<?php

namespace App\Models\Tipos;

/** Tipo Psíquico: HERDA de Tipo e só informa o que muda nele. */
class Psiquico extends Tipo
{
    /** Implementa o método ABSTRATO nome() de Tipo (obrigatório na filha). */
    public function nome(): string
    {
        return 'Psíquico';
    }

    /** SOBRESCREVE o método da classe mãe: dano x2 contra estes tipos. */
    protected function superEfetivoContra(): array
    {
        return ['Lutador', 'Venenoso'];
    }

    /** SOBRESCREVE: dano x0,5 contra estes tipos. */
    protected function poucoEfetivoContra(): array
    {
        return ['Aço', 'Psíquico'];
    }

    /** SOBRESCREVE: dano x0 (não afeta) contra estes tipos. */
    protected function semEfeitoContra(): array
    {
        return ['Sombrio'];
    }

    /** Golpes de Psíquico podem fazer dormir o alvo. */
    public function condicaoQueCausa(): ?string
    {
        return 'dormindo';
    }
}
