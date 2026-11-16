<?php

namespace App\Models;

use RuntimeException;

/**
 * Regra de negócio que escolhe os 4 golpes de uma espécie:
 *   1) golpe fraco do próprio tipo;
 *   2) golpe forte do próprio tipo (1º estágio de uma linha: Tackle/Quick Attack);
 *   3) golpe do 2º tipo ou, se a espécie não tiver, de um tipo de cobertura;
 *   4) golpe Normal.
 * Físico ou especial conforme o maior entre Ataque e Sp. Atk.
 * "Fraco" e "forte" = menor e maior poder entre os golpes cadastrados no banco.
 */
class SeletorDeGolpes
{
    public const QUANTIDADE = 4;

    /** Tipo de cobertura para quem só tem um tipo. */
    private const COBERTURA = [
        'Fogo' => 'Terra', 'Água' => 'Gelo', 'Planta' => 'Venenoso', 'Elétrico' => 'Aço',
        'Gelo' => 'Água', 'Lutador' => 'Pedra', 'Venenoso' => 'Sombrio', 'Terra' => 'Pedra',
        'Psíquico' => 'Fada', 'Inseto' => 'Venenoso', 'Pedra' => 'Terra', 'Fantasma' => 'Sombrio',
        'Dragão' => 'Fogo', 'Normal' => 'Sombrio', 'Fada' => 'Psíquico', 'Voador' => 'Normal',
        'Sombrio' => 'Fantasma', 'Aço' => 'Pedra',
    ];

    /** @var array<string, Golpe[]> golpes agrupados por tipo, do mais fraco ao mais forte */
    private array $porTipo = [];

    /** @var array<string, Golpe> */
    private array $porNome = [];

    /** @param Golpe[] $golpes */
    public function __construct(array $golpes)
    {
        foreach ($golpes as $g) {
            $this->porTipo[$g->getTipo()][] = $g;
            $this->porNome[$g->getNome()] = $g;
        }
        foreach ($this->porTipo as &$lista) {
            // empate no poder: desempata pelo nome, para o resultado não
            // depender da ordem em que os golpes foram inseridos no banco
            usort($lista, fn(Golpe $a, Golpe $b) =>
                [$a->getPoder(), $a->getNome()] <=> [$b->getPoder(), $b->getNome()]);
        }
        unset($lista); // desfaz a referência do foreach
    }

    /** @return Golpe[] */
    public function escolher(
        string $tipo1,
        ?string $tipo2,
        int $ataque,
        int $spAtk,
        int $velocidade,
        bool $primeiroEstagio
    ): array {
        $cat     = $ataque >= $spAtk ? 'fisico' : 'especial';
        $rapido  = $velocidade >= 60;
        $outro   = $tipo2 ?? self::COBERTURA[$tipo1] ?? 'Normal';
        $escolha = [];

        $add = function (?Golpe $g) use (&$escolha): void {
            if ($g !== null && count($escolha) < self::QUANTIDADE) {
                $escolha[$g->getId()] = $g; // a chave pelo id evita golpe repetido
            }
        };

        $add($this->fraco($tipo1, $cat));
        $add($primeiroEstagio
            ? $this->nome($rapido ? 'Quick Attack' : 'Tackle')
            : $this->forte($tipo1, $cat));
        $add($primeiroEstagio ? $this->fraco($outro, $cat) : $this->forte($outro, $cat));
        $add($primeiroEstagio
            ? $this->nome($rapido ? 'Tackle' : 'Quick Attack')
            : $this->nome($cat === 'fisico' ? 'Body Slam' : 'Hyper Voice'));

        // se algum se repetiu, completa com golpes Normal (do mais fraco ao mais forte)
        foreach ($this->porTipo['Normal'] ?? [] as $g) {
            $add($g);
        }

        if (count($escolha) < self::QUANTIDADE) {
            throw new RuntimeException('Golpes insuficientes no banco para montar 4 por espécie.');
        }
        return array_values($escolha);
    }

    /** Golpes do tipo na categoria pedida; se não houver nenhum, de qualquer categoria. */
    private function candidatos(string $tipo, string $categoria): array
    {
        $doTipo = $this->porTipo[$tipo] ?? [];
        $daCategoria = array_values(array_filter(
            $doTipo,
            fn(Golpe $g) => $g->getCategoria() === $categoria
        ));
        return $daCategoria ?: $doTipo;
    }

    private function fraco(string $tipo, string $categoria): ?Golpe
    {
        return $this->candidatos($tipo, $categoria)[0] ?? null;
    }

    private function forte(string $tipo, string $categoria): ?Golpe
    {
        $lista = $this->candidatos($tipo, $categoria);
        return $lista ? end($lista) : null;
    }

    private function nome(string $nome): ?Golpe
    {
        return $this->porNome[$nome] ?? null;
    }
}
