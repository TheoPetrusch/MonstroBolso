<?php
namespace App\Models;

/**
 * Pokémon do jogador (instância). ASSOCIAÇÃO com Especie:
 * a espécie existe independente do Pokémon.
 */
class Pokemon
{
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
        private ?int $posicao = null
    ) {
        $this->setHpAtual($hpAtual);
    }

    /** Linha vinda da view vw_time_completo + dados da espécie. */
    public static function fromRow(array $r): self
    {
        return new self(
            (int) $r['pokemon_id'], (int) $r['usuario_id'], Especie::fromRow($r),
            $r['apelido'] ?? null, (int) $r['nivel'], (int) $r['hp_max'],
            (int) $r['hp_atual'], $r['status'], (bool) $r['desmaiado'],
            isset($r['posicao']) ? (int) $r['posicao'] : null
        );
    }

    public function getId(): ?int { return $this->id; }
    public function getUsuarioId(): int { return $this->usuarioId; }
    public function getEspecie(): Especie { return $this->especie; }
    public function getNivel(): int { return $this->nivel; }
    public function getHpMax(): int { return $this->hpMax; }
    public function getHpAtual(): int { return $this->hpAtual; }
    public function getPosicao(): ?int { return $this->posicao; }
    public function estaDesmaiado(): bool { return $this->desmaiado; }
    public function getNomeExibicao(): string { return $this->apelido ?: $this->especie->getNome(); }

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
            'especie'   => $this->especie->paraArray(),
        ];
    }
}
