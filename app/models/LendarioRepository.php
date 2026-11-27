<?php

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Batalhas lendárias.
 * Os lendários e míticos (de todas as gerações importadas) começam BLOQUEADOS no
 * Team Builder. A cada VITORIAS_NECESSARIAS vitórias contra treinadores
 * comuns, o jogador pode desafiar um lendário SORTEADO entre os que ele
 * ainda não tem. Lendário com evolução: a batalha é contra o ÚLTIMO estágio
 * (Urshifu), mas quem fica desbloqueado é a BASE da linha (Kubfu), que o
 * jogador evolui com pedra. Ganhou? O trigger trg_batalhas_lendario
 * desbloqueia. Ganhando ou perdendo, a contagem de vitórias volta a zero.
 * Nada é guardado em contador: tudo sai do LOG de batalhas pela
 * VIEW vw_progresso_ginasio (coluna vitorias_desde_lendario).
 */
class LendarioRepository
{
    /** Vitórias contra treinadores comuns para poder desafiar um lendário. */
    public const VITORIAS_NECESSARIAS = 10;

    /** Dificuldade: o HP do lendário é multiplicado por este valor. */
    public const BONUS_HP = 5.0;

    private PDO $pdo;
    private EspecieRepository $especies;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
        $this->especies = new EspecieRepository($this->pdo);
    }

    /**
     * Ex.: ['vitorias' => 5, 'necessarias' => 8, 'liberado' => false,
     *       'capturados' => 1, 'total' => 4, 'restantes' => 3]
     * total = lendários de batalha (último estágio); capturado = o jogador
     * já desbloqueou a BASE da linha dele (ex.: Urshifu conta se tem o Kubfu).
     */
    public function progresso(int $usuarioId): array
    {
        $st = $this->pdo->prepare(
            'SELECT vitorias_desde_lendario FROM vw_progresso_ginasio WHERE usuario_id = :u'
        );
        $st->execute(['u' => $usuarioId]);
        $vitorias = (int) $st->fetchColumn();

        $total = count($this->especies->lendariosFinais());
        $restantes = count($this->naoCapturados($usuarioId));

        return [
            'vitorias' => min($vitorias, self::VITORIAS_NECESSARIAS),
            'necessarias' => self::VITORIAS_NECESSARIAS,
            'liberado' => $restantes > 0 && $vitorias >= self::VITORIAS_NECESSARIAS,
            'capturados' => $total - $restantes,
            'total' => $total,
            'restantes' => $restantes,
        ];
    }

    /**
     * Sorteia a batalha lendária entre os lendários que o jogador ainda não tem.
     * Devolve [lendário da batalha, base que será desbloqueada] ou null.
     * Ex.: [Urshifu, Kubfu] -> luta contra o Urshifu, ganha o Kubfu
     *      (e evolui ele com pedra depois). Sem evolução: [Mewtwo, Mewtwo].
     * @return array{0: Especie, 1: Especie}|null
     */
    public function sortear(int $usuarioId): ?array
    {
        $opcoes = $this->naoCapturados($usuarioId);
        if (!$opcoes) {
            return null;
        }
        [$lendario, $baseId] = $opcoes[array_rand($opcoes)];
        return [$lendario, $this->especies->buscarPorId($baseId) ?? $lendario];
    }

    /**
     * Lendários de batalha cuja BASE o jogador ainda não desbloqueou.
     * @return array<int, array{0: Especie, 1: int}> [lendário, id da base]
     */
    private function naoCapturados(int $usuarioId): array
    {
        $desbloqueadas = array_flip($this->especies->idsDesbloqueados($usuarioId));
        $linhas = $this->especies->linhas(); // id da espécie => id da base da linha
        $opcoes = [];
        foreach ($this->especies->lendariosFinais() as $e) {
            $base = $linhas[$e->getId()] ?? $e->getId();
            if (!isset($desbloqueadas[$base])) {
                $opcoes[] = [$e, $base];
            }
        }
        return $opcoes;
    }
}
