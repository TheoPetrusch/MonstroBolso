<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Batalha;
use App\Models\BatalhaRepository;
use App\Models\GinasioRepository;
use App\Models\ItemRepository;
use App\Models\Lider;
use InvalidArgumentException;

/**
 * Tela e API da batalha.
 * A batalha em andamento fica em $_SESSION['batalha'] (o objeto Batalha inteiro);
 * quando acaba, é gravada no banco e sai da sessão.
 */
class BatalhaController extends BaseController
{
    private BatalhaRepository $batalhas;
    private GinasioRepository $ginasios;
    private ItemRepository $itens;

    public function __construct()
    {
        $this->batalhas = new BatalhaRepository();
        $this->ginasios = new GinasioRepository();
        $this->itens    = new ItemRepository();
    }

    // ---------- página ----------
    public function pagina(): void
    {
        if ($this->usuarioIdLogado() === null) {
            $this->redirecionar('/');
            return;
        }
        $this->view('batalha');
    }

    // ---------- API (JSON) ----------

    /** Estado atual: a batalha em andamento (ou null) e o histórico. */
    public function estado(): void
    {
        $uid = $this->exigirLoginApi();
        if ($uid === null) return;

        $this->json($this->resposta($uid, $_SESSION['batalha'] ?? null));
    }

    /** Corpo JSON: {} = treinador comum; { "lider": true } = próximo líder de ginásio. */
    public function iniciar(): void
    {
        $uid = $this->exigirLoginApi();
        if ($uid === null) return;

        try {
            // se já tem uma batalha em andamento, continua ela
            if (!isset($_SESSION['batalha'])) {
                $lider = null;
                if (!empty($this->corpoJson()['lider'])) {
                    $lider = $this->liderLiberado($uid);
                }
                $_SESSION['batalha'] = $this->batalhas->nova($uid, $lider);
            }
            $this->json($this->resposta($uid, $_SESSION['batalha']));
        } catch (InvalidArgumentException $e) {
            $this->json(['ok' => false, 'erro' => $e->getMessage()], 422);
        }
    }

    /**
     * Uma ação do jogador. Corpo JSON:
     *   { "tipo": "golpe",  "id": 12 }
     *   { "tipo": "item",   "id": 1, "alvo_id": 5 }   (alvo_id é opcional)
     *   { "tipo": "trocar", "id": 5 }
     */
    public function acao(): void
    {
        $uid = $this->exigirLoginApi();
        if ($uid === null) return;

        $batalha = $_SESSION['batalha'] ?? null;
        if (!$batalha instanceof Batalha) {
            $this->json(['ok' => false, 'erro' => 'Nenhuma batalha em andamento.'], 409);
            return;
        }

        try {
            $d  = $this->corpoJson();
            $id = (int) ($d['id'] ?? 0);

            switch ($d['tipo'] ?? '') {
                case 'golpe':
                    $batalha->jogarTurno($batalha->golpeDoAtivo($id));
                    break;

                case 'item':
                    $item = $this->itens->buscarPorId($id);
                    if (!$item || !$this->itens->temNaMochila($uid, $id)) {
                        throw new InvalidArgumentException('Você não tem esse item na mochila.');
                    }
                    $alvo = isset($d['alvo_id']) ? $batalha->pokemonDoJogador((int) $d['alvo_id']) : null;
                    $batalha->jogarTurno($item, $alvo); // lança exceção se o item não tiver efeito
                    $this->itens->gastarUm($uid, $id);  // só gasta depois de dar certo
                    break;

                case 'trocar':
                    $batalha->jogarTurno($batalha->pokemonDoJogador($id));
                    break;

                default:
                    throw new InvalidArgumentException('Ação inválida.');
            }

            $this->json($this->resposta($uid, $this->encerrarSeAcabou($uid, $batalha)));
        } catch (InvalidArgumentException $e) {
            $this->json(['ok' => false, 'erro' => $e->getMessage()], 422);
        }
    }

    public function fugir(): void
    {
        $uid = $this->exigirLoginApi();
        if ($uid === null) return;

        $batalha = $_SESSION['batalha'] ?? null;
        if (!$batalha instanceof Batalha) {
            $this->json(['ok' => false, 'erro' => 'Nenhuma batalha em andamento.'], 409);
            return;
        }
        $batalha->fugir();
        $this->json($this->resposta($uid, $this->encerrarSeAcabou($uid, $batalha)));
    }

    // ---------- auxiliares ----------

    /**
     * Regra dos ginásios: só enfrenta o PRÓXIMO líder da ordem, e só depois
     * de 10 vitórias contra treinadores comuns (contadas desde o último líder).
     */
    private function liderLiberado(int $uid): Lider
    {
        $p = $this->ginasios->progresso($uid);
        if ($p['proximo'] === null) {
            throw new InvalidArgumentException('Você já venceu todos os líderes de ginásio!');
        }
        if (!$p['liberado']) {
            $faltam = $p['necessarias'] - $p['vitorias'];
            throw new InvalidArgumentException(
                "Vença mais {$faltam} treinador(es) para desafiar {$p['proximo']->getNome()}."
            );
        }
        return $p['proximo'];
    }

    /** Se a batalha acabou: grava no banco (log + prêmio) e tira da sessão. */
    private function encerrarSeAcabou(int $uid, Batalha $batalha): Batalha
    {
        if ($batalha->encerrada()) {
            $this->batalhas->salvar($uid, $batalha);
            unset($_SESSION['batalha']);
        }
        return $batalha;
    }

    private function resposta(int $uid, ?Batalha $batalha): array
    {
        // pedras de evolução não servem na batalha
        $mochila = array_values(array_filter(
            $this->itens->inventario($uid),
            fn(array $i) => $i['categoria'] !== 'evolucao'
        ));

        $progresso = $this->ginasios->progresso($uid);
        $progresso['proximo'] = $progresso['proximo']?->paraArray();

        return [
            'ok'        => true,
            'batalha'   => $batalha?->paraArray(),
            'progresso' => $progresso,
            'mochila'   => $mochila,
            'historico' => $this->batalhas->historico($uid),
        ];
    }
}
