<?php

namespace App\Models;

use InvalidArgumentException;

/**
 * Batalha do jogador contra o computador.
 *
 * - COMPOSIÇÃO: a batalha cria e guarda os seus Turnos (sem batalha, não há turno).
 * - SOBRECARGA (simulada): jogarTurno() recebe um Golpe, um Item ou um Pokemon
 *   e faz uma coisa diferente para cada um (ver comentário do método).
 * - POLIMORFISMO: o dano usa $golpe->tipo()->multiplicadorContra(...), e cada
 *   classe de tipo (Fogo, Agua...) responde do seu jeito.
 *
 * O objeto fica guardado na sessão ($_SESSION) enquanto a batalha acontece;
 * só vai para o banco quando acaba (BatalhaRepository::salvar).
 */
class Batalha
{
    public const PREMIO_POR_POKEMON = 150; // Pokédollar por Pokémon do oponente
    public const CHANCE_CONDICAO   = 10;  // % de um golpe causar condição

    /** Como cada condição aparece nas mensagens. */
    private const TEXTO_STATUS = [
        'veneno'     => 'envenenado',
        'queimado'   => 'queimado',
        'congelado'  => 'congelado',
        'dormindo'   => 'dormindo',
        'paralisado' => 'paralisado',
    ];

    /** @var Turno[] */
    private array $turnos = [];
    private int $numero = 0;
    private int $ativoJogador;
    private int $ativoOponente = 0;
    private ?string $vencedor = null; // 'jogador', 'oponente' ou null (em andamento)

    /**
     * @param Pokemon[] $jogador        time do jogador (na ordem dos slots)
     * @param Golpe[][] $golpesJogador  golpes de cada um, no mesmo índice
     * @param Pokemon[] $oponente       Pokémon sorteados para o computador
     * @param Golpe[][] $golpesOponente
     */
    public function __construct(
        private array $jogador,
        private array $golpesJogador,
        private array $oponente,
        private array $golpesOponente,
        private string $oponenteNome
    ) {
        $primeiro = $this->proximoDePe($this->jogador);
        if ($primeiro === null) {
            throw new InvalidArgumentException(
                'Nenhum Pokémon do seu time está de pé. Cure o time de graça no Centro Pokémon (Team Builder).'
            );
        }
        $this->ativoJogador = $primeiro;
    }

    // ================= ações do jogador =================

    /**
     * SOBRECARGA SIMULADA: o PHP não deixa ter três métodos com o mesmo nome,
     * então um método só aceita três tipos de parâmetro e decide pelo tipo:
     *   - Golpe   -> ataca;
     *   - Item    -> usa o item num Pokémon do jogador ($alvo; padrão = o que está lutando);
     *   - Pokemon -> troca o Pokémon que está lutando.
     * Depois da ação do jogador, o computador ataca.
     */
    public function jogarTurno(Golpe|Item|Pokemon $acao, ?Pokemon $alvo = null): void
    {
        if ($this->encerrada()) {
            throw new InvalidArgumentException('Essa batalha já acabou.');
        }

        $meu       = $this->ativoDoJogador();
        $dele      = $this->ativoDoOponente();
        $golpeDele = $this->golpeAleatorioDoOponente();

        if ($acao instanceof Golpe) {
            $golpe = $this->golpeDoAtivo($acao->getId()); // só golpes que ele conhece
            $this->numero++;
            if ($this->jogadorAgePrimeiro($meu, $dele)) {
                $this->atacar($meu, $dele, $golpe, 'jogador');
                $this->atacar($dele, $meu, $golpeDele, 'oponente');
            } else {
                $this->atacar($dele, $meu, $golpeDele, 'oponente');
                $this->atacar($meu, $dele, $golpe, 'jogador');
            }
        } elseif ($acao instanceof Item) {
            $alvo ??= $meu;
            $this->indiceDoJogador($alvo);   // confere se o alvo é do jogador
            $alvo->usarItem($acao);          // regra do Pokemon: lança exceção se não tiver efeito
            $this->numero++;
            $this->registrar('jogador', $acao->getNome(), 0, $alvo->getHpAtual(),
                "Você usou {$acao->getNome()} em {$alvo->getNomeExibicao()}.");
            $this->atacar($dele, $meu, $golpeDele, 'oponente');
        } else {
            $indice = $this->indiceDoJogador($acao);
            if ($indice === $this->ativoJogador) {
                throw new InvalidArgumentException("{$acao->getNomeExibicao()} já está lutando.");
            }
            if ($acao->estaDesmaiado()) {
                throw new InvalidArgumentException("{$acao->getNomeExibicao()} está desmaiado.");
            }
            $this->numero++;
            $this->ativoJogador = $indice;
            $this->registrar('jogador', 'Troca', 0, $acao->getHpAtual(),
                "Volte, {$meu->getNomeExibicao()}! Vai, {$acao->getNomeExibicao()}!");
            $this->atacar($dele, $acao, $golpeDele, 'oponente');
        }

        $this->fimDoTurno();
    }

    /** Fugir conta como derrota (e também vai para o log). */
    public function fugir(): void
    {
        if ($this->encerrada()) {
            throw new InvalidArgumentException('Essa batalha já acabou.');
        }
        $this->numero++;
        $this->registrar('jogador', 'Fugiu', 0, $this->ativoDoJogador()->getHpAtual(), 'Você fugiu da batalha.');
        $this->vencedor = 'oponente';
    }

    // ================= regras do turno =================

    /** Mais rápido ataca primeiro; empate = sorteio. */
    private function jogadorAgePrimeiro(Pokemon $meu, Pokemon $dele): bool
    {
        $v1 = $meu->getEspecie()->getVelocidade();
        $v2 = $dele->getEspecie()->getVelocidade();
        return $v1 === $v2 ? random_int(0, 1) === 1 : $v1 > $v2;
    }

    private function atacar(Pokemon $atacante, Pokemon $defensor, Golpe $golpe, string $ator): void
    {
        // quem desmaiou antes de agir não ataca
        if ($atacante->estaDesmaiado() || $defensor->estaDesmaiado()) {
            return;
        }
        if (!$this->podeAgir($atacante, $ator)) {
            return;
        }

        $quem = $this->nome($atacante, $ator);
        $alvo = $this->nome($defensor, $ator === 'jogador' ? 'oponente' : 'jogador');

        if (random_int(1, 100) > $golpe->getPrecisao()) {
            $this->registrar($ator, $golpe->getNome(), 0, $defensor->getHpAtual(),
                "{$quem} usou {$golpe->getNome()}, mas errou!");
            return;
        }

        // POLIMORFISMO: cada tipo (Fogo, Agua...) calcula a sua vantagem
        $multiplicador = $golpe->tipo()->multiplicadorContra($defensor->tipo());
        $dano = $this->calcularDano($atacante, $defensor, $golpe, $multiplicador);
        $defensor->setHpAtual($defensor->getHpAtual() - $dano);

        $msg = "{$quem} usou {$golpe->getNome()}";
        if ($multiplicador == 0) {
            $msg .= ", mas não teve efeito em {$alvo}.";
        } else {
            $msg .= " e causou {$dano} de dano.";
            if ($multiplicador > 1) {
                $msg .= ' É super efetivo!';
            } elseif ($multiplicador < 1) {
                $msg .= ' Não é muito efetivo...';
            }
        }

        // chance de causar condição (o tipo do golpe diz qual: Fogo queima...)
        $condicao = $golpe->tipo()->condicaoQueCausa();
        if ($condicao !== null && $dano > 0 && !$defensor->estaDesmaiado()
            && $defensor->getStatus() === 'normal' && random_int(1, 100) <= self::CHANCE_CONDICAO) {
            $defensor->setStatus($condicao);
            $msg .= " {$alvo} ficou " . self::TEXTO_STATUS[$condicao] . '!';
        }

        if ($defensor->estaDesmaiado()) {
            $msg .= " {$alvo} desmaiou!";
        }
        $this->registrar($ator, $golpe->getNome(), $dano, $defensor->getHpAtual(), $msg);
    }

    /**
     * Fórmula simplificada:  poder x ataque ÷ (defesa x 5) x vantagem de tipo,
     * com uma variação aleatória de 85% a 100%. Físico usa Ataque/Defesa;
     * especial usa Sp. Atk/Sp. Def.
     */
    private function calcularDano(Pokemon $atacante, Pokemon $defensor, Golpe $golpe, float $multiplicador): int
    {
        if ($multiplicador == 0) {
            return 0;
        }
        $a = $atacante->getEspecie();
        $d = $defensor->getEspecie();
        [$ataque, $defesa] = $golpe->getCategoria() === 'fisico'
            ? [$a->getAtaque(), $d->getDefesa()]
            : [$a->getSpAtk(), $d->getSpDef()];

        $base = $golpe->getPoder() * $ataque / ($defesa * 5);
        $dano = (int) round($base * $multiplicador * random_int(85, 100) / 100);
        return max(1, $dano);
    }

    /**
     * Condições que podem fazer perder a vez:
     *   - paralisado: 25% de chance de não se mexer;
     *   - dormindo/congelado: 50% de continuar assim; senão acorda/descongela e ataca.
     */
    private function podeAgir(Pokemon $p, string $ator): bool
    {
        $nome = $this->nome($p, $ator);
        $status = $p->getStatus();

        if ($status === 'paralisado' && random_int(1, 100) <= 25) {
            $this->registrar($ator, 'Perdeu a vez', 0, $p->getHpAtual(), "{$nome} está paralisado e não conseguiu se mexer!");
            return false;
        }
        if ($status === 'dormindo' || $status === 'congelado') {
            if (random_int(1, 100) <= 50) {
                $this->registrar($ator, 'Perdeu a vez', 0, $p->getHpAtual(),
                    "{$nome} está " . self::TEXTO_STATUS[$status] . ' e não pode agir.');
                return false;
            }
            $p->setStatus('normal');
            $this->registrar($ator, $status === 'dormindo' ? 'Acordou' : 'Descongelou', 0, $p->getHpAtual(),
                $status === 'dormindo' ? "{$nome} acordou!" : "{$nome} descongelou!");
        }
        return true;
    }

    /** Veneno e queimadura tiram 1/8 do HP máximo; depois troca quem desmaiou. */
    private function fimDoTurno(): void
    {
        foreach (['jogador' => $this->ativoDoJogador(), 'oponente' => $this->ativoDoOponente()] as $ator => $p) {
            if (!$p->estaDesmaiado() && in_array($p->getStatus(), ['veneno', 'queimado'], true)) {
                $dano = max(1, intdiv($p->getHpMax(), 8));
                $p->setHpAtual($p->getHpAtual() - $dano);
                $causa = $p->getStatus() === 'veneno' ? 'Veneno' : 'Queimadura';
                $msg = "{$this->nome($p, $ator)} sofreu {$dano} de dano por " . mb_strtolower($causa) . '.';
                if ($p->estaDesmaiado()) {
                    $msg .= " {$this->nome($p, $ator)} desmaiou!";
                }
                $this->registrar($ator, $causa, $dano, $p->getHpAtual(), $msg);
            }
        }

        if ($this->ativoDoOponente()->estaDesmaiado()) {
            $proximo = $this->proximoDePe($this->oponente);
            if ($proximo === null) {
                $this->vencedor = 'jogador';
                return;
            }
            $this->ativoOponente = $proximo;
            $novo = $this->ativoDoOponente();
            $this->registrar('oponente', 'Troca', 0, $novo->getHpAtual(),
                "{$this->oponenteNome} enviou {$novo->getNomeExibicao()}!");
        }

        if ($this->ativoDoJogador()->estaDesmaiado()) {
            $proximo = $this->proximoDePe($this->jogador);
            if ($proximo === null) {
                $this->vencedor = 'oponente';
                return;
            }
            $this->ativoJogador = $proximo;
            $novo = $this->ativoDoJogador();
            $this->registrar('jogador', 'Troca', 0, $novo->getHpAtual(), "Vai, {$novo->getNomeExibicao()}!");
        }
    }

    // ================= auxiliares =================

    /** COMPOSIÇÃO: os Turnos são criados aqui dentro, e só aqui. */
    private function registrar(string $ator, string $acao, int $dano, int $hp, string $mensagem): void
    {
        $this->turnos[] = new Turno($this->numero, $ator, mb_substr($acao, 0, 60), $dano, $hp, $mensagem);
    }

    private function nome(Pokemon $p, string $ator): string
    {
        return $ator === 'jogador' ? $p->getNomeExibicao() : "{$p->getNomeExibicao()} do oponente";
    }

    /** @param Pokemon[] $time  devolve o índice do 1º que está de pé, ou null */
    private function proximoDePe(array $time): ?int
    {
        foreach ($time as $i => $p) {
            if (!$p->estaDesmaiado()) {
                return $i;
            }
        }
        return null;
    }

    private function golpeAleatorioDoOponente(): Golpe
    {
        $golpes = $this->golpesOponente[$this->ativoOponente];
        return $golpes[array_rand($golpes)];
    }

    /** Índice do Pokémon no time do jogador (pelo id do banco). */
    private function indiceDoJogador(Pokemon $p): int
    {
        foreach ($this->jogador as $i => $meu) {
            if ($meu->getId() === $p->getId()) {
                return $i;
            }
        }
        throw new InvalidArgumentException('Esse Pokémon não está na batalha.');
    }

    // ================= usados pelo controller =================

    /** Golpe do Pokémon do jogador que está lutando, pelo id. */
    public function golpeDoAtivo(int $golpeId): Golpe
    {
        foreach ($this->golpesJogador[$this->ativoJogador] as $g) {
            if ($g->getId() === $golpeId) {
                return $g;
            }
        }
        throw new InvalidArgumentException('Seu Pokémon não conhece esse golpe.');
    }

    /** Pokémon do jogador pelo id do banco. */
    public function pokemonDoJogador(int $pokemonId): Pokemon
    {
        foreach ($this->jogador as $p) {
            if ($p->getId() === $pokemonId) {
                return $p;
            }
        }
        throw new InvalidArgumentException('Esse Pokémon não está na batalha.');
    }

    public function ativoDoJogador(): Pokemon
    {
        return $this->jogador[$this->ativoJogador];
    }
    public function ativoDoOponente(): Pokemon
    {
        return $this->oponente[$this->ativoOponente];
    }
    public function encerrada(): bool
    {
        return $this->vencedor !== null;
    }
    public function getVencedor(): ?string
    {
        return $this->vencedor;
    }
    public function getOponenteNome(): string
    {
        return $this->oponenteNome;
    }
    public function getNumero(): int
    {
        return $this->numero;
    }
    /** @return Pokemon[] */
    public function getJogador(): array
    {
        return $this->jogador;
    }
    /** @return Turno[] */
    public function getTurnos(): array
    {
        return $this->turnos;
    }

    /** Prêmio só para quem venceu: 150 por Pokémon do oponente (mais inimigos = mais dinheiro). */
    public function premio(): int
    {
        return $this->vencedor === 'jogador' ? self::PREMIO_POR_POKEMON * count($this->oponente) : 0;
    }

    public function paraArray(): array
    {
        $lutador = fn(Pokemon $p, int $i) => [
            'indice'     => $i,
            'id'         => $p->getId(),
            'nome'       => $p->getNomeExibicao(),
            'tipo'       => $p->getEspecie()->getTipo(),
            'pokeapi_id' => $p->getEspecie()->getPokeapiId(),
            'hp_atual'   => $p->getHpAtual(),
            'hp_max'     => $p->getHpMax(),
            'status'     => $p->getStatus(),
            'desmaiado'  => $p->estaDesmaiado(),
            'shiny'      => $p->isShiny(),
        ];

        return [
            'turno'         => $this->numero,
            'encerrada'     => $this->encerrada(),
            'vencedor'      => $this->vencedor,
            'premio'        => $this->premio(),
            'oponente_nome' => $this->oponenteNome,
            'jogador'  => [
                'ativo'    => $this->ativoJogador,
                'pokemons' => array_map($lutador, $this->jogador, array_keys($this->jogador)),
            ],
            'oponente' => [
                'ativo'    => $this->ativoOponente,
                'pokemons' => array_map($lutador, $this->oponente, array_keys($this->oponente)),
            ],
            'golpes' => array_map(fn(Golpe $g) => $g->paraArray(), $this->golpesJogador[$this->ativoJogador]),
            'log'    => array_map(fn(Turno $t) => $t->paraArray(), $this->turnos),
        ];
    }
}
