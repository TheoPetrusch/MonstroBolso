<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Exceptions\TimeCheioException;
use App\Models\EspecieRepository;
use App\Models\Especie;
use App\Models\ItemRepository;
use App\Models\TimeRepository;
use InvalidArgumentException;

class TimeController extends BaseController
{
    private TimeRepository $times;
    private EspecieRepository $especies;
    private ItemRepository $itens;

    public function __construct()
    {
        $this->times    = new TimeRepository();
        $this->especies = new EspecieRepository();
        $this->itens    = new ItemRepository();
    }

    // ---------- página ----------
    public function pagina(): void
    {
        if ($this->usuarioIdLogado() === null) {
            $this->redirecionar('/');
            return;
        }
        $this->view('team');
    }

    // ---------- API (JSON) ----------

    public function listarEspecies(): void
    {
        $uid = $this->exigirLoginApi();
        if ($uid === null) return;

        // todas aparecem; "disponivel" diz quais podem ser adicionadas:
        // as básicas e as evoluções que o jogador já conseguiu com pedra
        // "linha" = espécie base da linha evolutiva: a tela usa para não
        // deixar adicionar duas da mesma linha (o back-end também confere)
        $desbloqueadas = $this->especies->idsDesbloqueados($uid);
        $linhas = $this->especies->linhas();
        $lista = array_map(fn(Especie $e) => $e->paraArray() + [
            'disponivel' => $e->isBasica() || in_array($e->getId(), $desbloqueadas, true),
            'linha'      => $linhas[$e->getId()] ?? $e->getId(),
        ], $this->especies->listar());
        $this->json(['ok' => true, 'especies' => $lista]);
    }

    public function meuTime(): void
    {
        $uid = $this->exigirLoginApi();
        if ($uid === null) return;

        $this->json($this->respostaTime($uid));
    }

    public function adicionar(): void
    {
        $uid = $this->exigirLoginApi();
        if ($uid === null) return;

        try {
            $id = (int) ($this->corpoJson()['especie_id'] ?? 0);
            $especie = $this->especies->buscarPorId($id);
            if (!$especie) {
                throw new InvalidArgumentException('Espécie não encontrada.');
            }
            // confere no back-end também: não basta esconder no catálogo,
            // alguém poderia mandar o especie_id de um Charizard direto pela API
            if (!$especie->isBasica()
                && !in_array($especie->getId(), $this->especies->idsDesbloqueados($uid), true)) {
                throw new InvalidArgumentException(
                    'Essa evolução ainda está bloqueada. Evolua um Pokémon com pedra para desbloqueá-la.'
                );
            }

            $novo = $this->times->adicionarEspecie($uid, $especie);
            $this->json($this->respostaTime($uid) + ['shiny' => $novo->isShiny()], 201);
        } catch (TimeCheioException $e) {
            $this->json(['ok' => false, 'erro' => $e->getMessage()], $e->getCode());
        } catch (InvalidArgumentException $e) {
            $this->json(['ok' => false, 'erro' => $e->getMessage()], 422);
        }
    }

    public function remover(): void
    {
        $uid = $this->exigirLoginApi();
        if ($uid === null) return;

        $id = (int) ($this->corpoJson()['pokemon_id'] ?? 0);
        if (!$this->times->remover($uid, $id)) {
            $this->json(['ok' => false, 'erro' => 'Pokémon não encontrado.'], 404);
            return;
        }
        $this->json($this->respostaTime($uid));
    }

    /** Remove todos os Pokémon do time de uma vez. */
    public function limpar(): void
    {
        $uid = $this->exigirLoginApi();
        if ($uid === null) return;

        $removidos = $this->times->limpar($uid);
        $this->json($this->respostaTime($uid) + ['removidos' => $removidos]);
    }

    /** Evolui um Pokémon do time gastando uma pedra da mochila. */
    public function evoluir(): void
    {
        $uid = $this->exigirLoginApi();
        if ($uid === null) return;

        try {
            $d = $this->corpoJson();
            $pokemon = $this->times->evoluir(
                $uid,
                (int) ($d['pokemon_id'] ?? 0),
                (int) ($d['item_id'] ?? 0),
                (int) ($d['para_especie_id'] ?? 0)
            );
            $this->json($this->respostaTime($uid) + ['evoluiu_para' => $pokemon->getEspecie()->getNome()]);
        } catch (InvalidArgumentException $e) {
            $this->json(['ok' => false, 'erro' => $e->getMessage()], 422);
        }
    }

    /** Usa um item de cura da mochila (Potion, Antidote, Revive...) num Pokémon do time. */
    public function usarItem(): void
    {
        $uid = $this->exigirLoginApi();
        if ($uid === null) return;

        try {
            $d = $this->corpoJson();
            $this->times->usarItem(
                $uid,
                (int) ($d['pokemon_id'] ?? 0),
                (int) ($d['item_id'] ?? 0)
            );
            $this->json($this->respostaTime($uid));
        } catch (InvalidArgumentException $e) {
            $this->json(['ok' => false, 'erro' => $e->getMessage()], 422);
        }
    }

    /** Deixa um Pokémon do time shiny (ou volta ao normal), se a espécie estiver desbloqueada. */
    public function alternarShiny(): void
    {
        $uid = $this->exigirLoginApi();
        if ($uid === null) return;

        try {
            $p = $this->times->alternarShiny($uid, (int) ($this->corpoJson()['pokemon_id'] ?? 0));
            $this->json($this->respostaTime($uid) + ['shiny' => $p->isShiny()]);
        } catch (InvalidArgumentException $e) {
            $this->json(['ok' => false, 'erro' => $e->getMessage()], 422);
        }
    }

    /**
     * Centro Pokémon: cura o time de graça. Não vale no meio de uma batalha
     * (lá dentro, só o item Revive revive).
     */
    public function centroPokemon(): void
    {
        $uid = $this->exigirLoginApi();
        if ($uid === null) return;

        if (isset($_SESSION['batalha'])) {
            $this->json(['ok' => false, 'erro' => 'Termine a batalha antes de ir ao Centro Pokémon.'], 409);
            return;
        }
        $curados = $this->times->centroPokemon($uid);
        $this->json($this->respostaTime($uid) + ['curados' => $curados]);
    }

    /** Time + evoluções + itens de cura: o que a tela precisa depois de qualquer ação. */
    private function respostaTime(int $uid): array
    {
        // pedras ficam de fora: elas aparecem nos botões "Evoluir"
        $mochila = array_values(array_filter(
            $this->itens->inventario($uid),
            fn(array $i) => $i['categoria'] !== 'evolucao'
        ));

        return [
            'ok'        => true,
            'time'      => $this->times->carregar($uid)->paraArray(),
            'evolucoes' => $this->times->opcoesDeEvolucao($uid),
            'mochila'   => $mochila,
            'shinies'   => $this->times->shiniesDesbloqueados($uid), // espécies com shiny liberado
        ];
    }
}
