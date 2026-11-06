<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Exceptions\TimeCheioException;
use App\Models\EspecieRepository;
use App\Models\Especie;
use App\Models\TimeRepository;
use InvalidArgumentException;

class TimeController extends BaseController
{
    private TimeRepository $times;
    private EspecieRepository $especies;

    public function __construct()
    {
        $this->times    = new TimeRepository();
        $this->especies = new EspecieRepository();
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
        if ($this->exigirLoginApi() === null) return;

        $lista = array_map(fn(Especie $e) => $e->paraArray(), $this->especies->listar());
        $this->json(['ok' => true, 'especies' => $lista]);
    }

    public function meuTime(): void
    {
        $uid = $this->exigirLoginApi();
        if ($uid === null) return;

        $this->json(['ok' => true, 'time' => $this->times->carregar($uid)->paraArray()]);
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

            $this->times->adicionarEspecie($uid, $especie);
            $this->json(['ok' => true, 'time' => $this->times->carregar($uid)->paraArray()], 201);
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
        $this->json(['ok' => true, 'time' => $this->times->carregar($uid)->paraArray()]);
    }
}
