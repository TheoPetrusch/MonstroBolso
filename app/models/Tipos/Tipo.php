<?php

namespace App\Models\Tipos;

use InvalidArgumentException;

/**
 * Classe ABSTRATA de todos os tipos (Fogo, Água...). Não dá para criar um
 * "Tipo" puro: só as classes filhas, que HERDAM daqui.
 *
 * POLIMORFISMO: a batalha chama $golpe->tipo()->multiplicadorContra(...)
 * sem saber se é Fogo ou Água; cada classe filha responde com as suas
 * próprias listas (superEfetivoContra, poucoEfetivoContra, semEfeitoContra).
 */
abstract class Tipo
{
    /** Nome do tipo, igual ao da tabela "tipos" do banco. */
    abstract public function nome(): string;

    /** Tipos que recebem dano DOBRADO deste (x2). As filhas sobrescrevem. */
    protected function superEfetivoContra(): array
    {
        return [];
    }

    /** Tipos que recebem METADE do dano deste (x0,5). */
    protected function poucoEfetivoContra(): array
    {
        return [];
    }

    /** Tipos que NÃO recebem dano deste (x0). */
    protected function semEfeitoContra(): array
    {
        return [];
    }

    /** Condição que golpes deste tipo podem causar (null = nenhuma). */
    public function condicaoQueCausa(): ?string
    {
        return null;
    }

    /** Multiplicador de dano de um golpe deste tipo contra o tipo do defensor. */
    public function multiplicadorContra(Tipo $defensor): float
    {
        $alvo = $defensor->nome();
        if (in_array($alvo, $this->semEfeitoContra(), true)) {
            return 0.0;
        }
        if (in_array($alvo, $this->superEfetivoContra(), true)) {
            return 2.0;
        }
        if (in_array($alvo, $this->poucoEfetivoContra(), true)) {
            return 0.5;
        }
        return 1.0;
    }

    /** Nome do banco -> classe PHP (as classes não usam acento no nome). */
    private const CLASSES = [
        'Normal'   => Normal::class,
        'Lutador'  => Lutador::class,
        'Voador'   => Voador::class,
        'Venenoso' => Venenoso::class,
        'Terra'    => Terra::class,
        'Pedra'    => Pedra::class,
        'Inseto'   => Inseto::class,
        'Fantasma' => Fantasma::class,
        'Aço'      => Aco::class,
        'Fogo'     => Fogo::class,
        'Água'     => Agua::class,
        'Planta'   => Planta::class,
        'Elétrico' => Eletrico::class,
        'Psíquico' => Psiquico::class,
        'Gelo'     => Gelo::class,
        'Dragão'   => Dragao::class,
        'Sombrio'  => Sombrio::class,
        'Fada'     => Fada::class,
    ];

    /** Cria o objeto do tipo pelo nome que vem do banco (ex.: 'Água' -> new Agua()). */
    public static function criar(string $nome): Tipo
    {
        $classe = self::CLASSES[$nome] ?? null;
        if ($classe === null) {
            throw new InvalidArgumentException("Tipo desconhecido: {$nome}");
        }
        return new $classe();
    }
}
