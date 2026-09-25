<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Services;

use App\Modules\Ranking\DTOs\FiltroPlacar;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Indicadores por linha da pagina do placar: entregas pontuadas, quantas no
 * prazo (com bonus) e a data da ultima entrega.
 *
 * FORA DA CONSULTA DO PLACAR, DE PROPOSITO
 * O LeaderboardQuery le so a projecao (ranking.saldos) e e cacheado por
 * pagina. Agregar o ledger dentro dele faria o DENSE_RANK varrer lancamentos
 * de todo o universo do periodo. Aqui a agregacao e so das entidades JA
 * paginadas (no maximo POR_PAGINA_MAXIMO), pelos indices
 * (entidade, competencia_em) do ledger.
 *
 * Estorno nao e entrega e fica fora da contagem; os pontos ja o refletem.
 *
 * CUIDADO COM OCTANE: stateless; conexao resolvida por nome a cada chamada.
 */
final class IndicadoresDoPlacar
{
    /**
     * @param array<int, array<string, mixed>> $linhas Linhas de LeaderboardQuery::pagina().
     *
     * @return array<int, array<string, mixed>> As mesmas linhas com entregas,
     *                                          entregas_no_prazo e ultima_entrega_em.
     */
    public function anexar(array $linhas, FiltroPlacar $filtro): array
    {
        if ($linhas === []) {
            return $linhas;
        }

        $ids = array_values(array_unique(array_map(static fn (array $l): int => (int) $l['entidade_id'], $linhas)));
        // Coluna vinda do enum, nunca da requisicao: seguro interpolar.
        $coluna = $filtro->escopo->colunaEntidade();

        $registros = DB::connection((string) config('ranking.conexao_leitura'))->select(
            "SELECT l.{$coluna} AS entidade_id,
                    COUNT(*) FILTER (WHERE l.estorno_de_id IS NULL AND l.pontos > 0)       AS entregas,
                    COUNT(*) FILTER (WHERE l.estorno_de_id IS NULL AND l.pontos_bonus > 0) AS entregas_no_prazo,
                    MAX(l.competencia_em) FILTER (WHERE l.estorno_de_id IS NULL AND l.pontos > 0) AS ultima_entrega_em
               FROM ranking.lancamentos l
               JOIN ranking.periodos p ON p.chave = ?
              WHERE l.{$coluna} = ANY(?::bigint[])
                AND (p.inicia_em IS NULL OR l.competencia_em >= p.inicia_em)
                AND (p.termina_em IS NULL OR l.competencia_em < p.termina_em)
                AND (? = 'all' OR l.modulo = ?)
              GROUP BY l.{$coluna}",
            [$filtro->periodoChave, '{' . implode(',', $ids) . '}', $filtro->modulo, $filtro->modulo],
        );

        $porEntidade = [];
        foreach ($registros as $registro) {
            $porEntidade[(int) $registro->entidade_id] = $registro;
        }

        return array_map(static function (array $linha) use ($porEntidade): array {
            $registro = $porEntidade[(int) $linha['entidade_id']] ?? null;

            return $linha + [
                'entregas' => (int) ($registro->entregas ?? 0),
                'entregas_no_prazo' => (int) ($registro->entregas_no_prazo ?? 0),
                // ISO 8601: o texto do timestamptz ('2026-09-24 13:00:00+00') nao e
                // lido de forma confiavel pelo Date do navegador.
                'ultima_entrega_em' => isset($registro->ultima_entrega_em)
                    ? (new DateTimeImmutable((string) $registro->ultima_entrega_em))->format(DATE_ATOM)
                    : null,
            ];
        }, $linhas);
    }
}
