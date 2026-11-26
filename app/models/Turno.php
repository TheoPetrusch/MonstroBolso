<?php

namespace App\Models;

/**
 * Um registro do que aconteceu na batalha (um golpe, um item, uma troca...).
 * COMPOSIÇÃO: só a Batalha cria Turnos, e eles não existem sem ela.
 * Cada Turno vira uma linha da tabela de log batalha_turnos.
 */
class Turno
{
    public function __construct(
        private int $numero,
        private string $ator,       // 'jogador' ou 'oponente'
        private string $acao,       // nome do golpe, do item ou "Troca"
        private int $dano,
        private int $hpRestante,    // HP de quem recebeu a ação
        private string $mensagem    // texto mostrado na tela
    ) {
    }

    public function getNumero(): int
    {
        return $this->numero;
    }
    public function getAtor(): string
    {
        return $this->ator;
    }
    public function getAcao(): string
    {
        return $this->acao;
    }
    public function getDano(): int
    {
        return $this->dano;
    }
    public function getHpRestante(): int
    {
        return $this->hpRestante;
    }

    public function paraArray(): array
    {
        return [
            'turno' => $this->numero,
            'ator' => $this->ator,
            'mensagem' => $this->mensagem,
        ];
    }
}
