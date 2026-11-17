<?php

namespace App\Models;

use InvalidArgumentException;

/**
 * Pokémon do jogador (instância). ASSOCIAÇÃO com Especie:
 * a espécie existe independente do Pokémon.
 */
class Pokemon
{
    /** Chance de nascer shiny: 1 em CHANCE_SHINY. */
    public const CHANCE_SHINY = 64;

    private int $hpAtual;

    public function __construct(
        private ?int $id,
        private int $usuarioId,
        private Especie $especie,
        private ?string $apelido,
        private int $nivel,
        private int $hpMax,
        int $hpAtual,
        private string $status = 'normal',
        private bool $desmaiado = false,
        private ?int $posicao = null,
        private bool $shiny = false
    ) {
        $this->setHpAtual($hpAtual);
    }

    /**
     * Regra de negócio: um Pokémon novo nasce no nível 5,
     * com HP máximo e atual iguais ao HP base da espécie,
     * e tem 1 chance em 64 de ser shiny (cor rara; continua shiny ao evoluir).
     */
    public static function novoDaEspecie(int $usuarioId, Especie $especie): self
    {
        return new self(
            null,
            $usuarioId,
            $especie,
            null,
            5,
            $especie->getHp(),
            $especie->getHp(),
            shiny: random_int(1, self::CHANCE_SHINY) === 1
        );
    }

    /** Linha vinda da view vw_time_completo (Pokémon + espécie). */
    public static function fromRow(array $r): self
    {
        // a view usa "especie_id" e "especie"; a Especie espera "id" e "nome"
        $especie = Especie::fromRow(array_merge($r, [
            'id'   => $r['especie_id'],
            'nome' => $r['especie'],
        ]));

        return new self(
            (int) $r['pokemon_id'],
            (int) $r['usuario_id'],
            $especie,
            $r['apelido'] ?? null,
            (int) $r['nivel'],
            (int) $r['hp_max'],
            (int) $r['hp_atual'],
            $r['status'],
            (bool) $r['desmaiado'],
            isset($r['posicao']) ? (int) $r['posicao'] : null,
            (bool) ($r['shiny'] ?? false)
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    public function isShiny(): bool
    {
        return $this->shiny;
    }
    public function getUsuarioId(): int
    {
        return $this->usuarioId;
    }
    public function getEspecie(): Especie
    {
        return $this->especie;
    }
    public function getNivel(): int
    {
        return $this->nivel;
    }
    public function getHpMax(): int
    {
        return $this->hpMax;
    }
    public function getHpAtual(): int
    {
        return $this->hpAtual;
    }
    public function getPosicao(): ?int
    {
        return $this->posicao;
    }
    public function estaDesmaiado(): bool
    {
        return $this->desmaiado;
    }
    public function getNomeExibicao(): string
    {
        return $this->apelido ?: $this->especie->getNome();
    }

    /**
     * Regra de negócio da evolução: o Pokémon vira a nova espécie, o HP
     * máximo passa a ser o da nova espécie e o HP atual ganha a mesma
     * diferença (ex.: Charmander 30/39 -> Charmeleon 49/58).
     * Quem confere se a pedra serve é o TimeRepository (pela vw_evolucoes).
     */
    public function evoluirPara(Especie $nova): void
    {
        if ($this->desmaiado) {
            throw new InvalidArgumentException('Pokémon desmaiado não pode evoluir. Use um Revive antes.');
        }
        $ganho = max(0, $nova->getHp() - $this->hpMax);
        $this->especie = $nova;
        $this->hpMax   = $nova->getHp();
        $this->setHpAtual($this->hpAtual + $ganho);
    }

    /**
     * Regra de negócio dos itens de cura. Lança exceção quando o item não
     * teria efeito, para o jogador não gastar item à toa.
     *   - reviver:     só em desmaiado; volta com X% do HP máximo;
     *   - cura_hp:     cura HP fixo e/ou percentual (Full Restore cura status também);
     *   - cura_status: cura a condição certa (ou todas, no Full Heal).
     */
    public function usarItem(Item $item): void
    {
        $nome = $this->getNomeExibicao();

        if ($item->getCategoria() === 'evolucao') {
            throw new InvalidArgumentException('Pedras de evolução são usadas no botão "Evoluir".');
        }

        if ($item->getCategoria() === 'reviver') {
            if (!$this->desmaiado) {
                throw new InvalidArgumentException("{$item->getNome()} só funciona em Pokémon desmaiado.");
            }
            $this->setHpAtual(max(1, intdiv($this->hpMax * $item->getCuraPercentual(), 100)));
            return;
        }

        if ($this->desmaiado) {
            throw new InvalidArgumentException("{$nome} está desmaiado: use um Revive primeiro.");
        }

        $cura = $item->getCuraHp() + intdiv($this->hpMax * $item->getCuraPercentual(), 100);
        $curaHp = $cura > 0 && $this->hpAtual < $this->hpMax;
        $curaStatus = $this->status !== 'normal'
            && ($item->curaTodosStatus() || $item->getStatusCurado() === $this->status);

        if (!$curaHp && !$curaStatus) {
            throw new InvalidArgumentException("{$item->getNome()} não teria efeito em {$nome}.");
        }
        if ($curaHp) {
            $this->setHpAtual($this->hpAtual + $cura);
        }
        if ($curaStatus) {
            $this->status = 'normal';
        }
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    /** HP nunca fica fora de 0..hpMax (encapsulamento). */
    public function setHpAtual(int $hp): void
    {
        $this->hpAtual = max(0, min($hp, $this->hpMax));
        $this->desmaiado = $this->hpAtual === 0;
    }

    public function paraArray(): array
    {
        return [
            'id'        => $this->id,
            'nome'      => $this->getNomeExibicao(),
            'posicao'   => $this->posicao,
            'nivel'     => $this->nivel,
            'hp_atual'  => $this->hpAtual,
            'hp_max'    => $this->hpMax,
            'status'    => $this->status,
            'desmaiado' => $this->desmaiado,
            'shiny'     => $this->shiny,
            'especie'   => $this->especie->paraArray(),
        ];
    }
}
