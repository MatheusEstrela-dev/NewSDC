<?php

declare(strict_types=1);

namespace App\Modules\Cedec\Services;

use App\Modules\Compdec\Support\MigracaoReport;
use Illuminate\Support\Facades\DB;

/**
 * Preenche `cedec_municipio.redec_id` a partir do legado gestaocedec.
 *
 * O espelho `cedec_municipio` nasce do dump database/data/cedec_municipio.sql, e
 * naquele arquivo a coluna redec_id vem NULL nas 854 linhas -- por isso a REDEC
 * aparecia vazia na listagem e o filtro por REDEC nao devolvia nada.
 *
 * O vinculo real mora em outra tabela do legado, `cedec_rpm_mun`: 854 linhas, uma
 * por municipio, ligando id_municipio a id_rpm. Os ids 1..19 de `id_rpm` batem um a
 * um com `dec_redecs.id`, que e o que a listagem usa no join. O id_rpm 20 existe
 * apenas para o municipio sentinela 7221 (MUNICIPIO TESTE) e e descartado, como ja
 * faz o ETL de prefeituras.
 */
final class RedecSincronizacaoService
{
    /**
     * Mesmo sentinela excluido por PrefeituraService::migrarLegado(): linha de teste
     * do legado, sem municipio correspondente no NewSDC.
     */
    private const MUNICIPIO_SENTINELA = 7221;

    public function sincronizar(bool $dryRun = false): MigracaoReport
    {
        $report = new MigracaoReport('redecs');
        $report->dryRun = $dryRun;

        $vinculos = $this->vinculosDoLegado();

        if ($vinculos === []) {
            return $report;
        }

        $redecsConhecidas = DB::table('dec_redecs')->pluck('id')->all();
        $redecsConhecidas = array_flip(array_map('intval', $redecsConhecidas));

        $espelho = DB::table('cedec_municipio')
            ->whereIn('id', array_keys($vinculos))
            ->pluck('redec_id', 'id');

        // Agrupa por REDEC para gravar com um UPDATE por REDEC (no maximo 19) em vez
        // de um por municipio. Sao 853 municipios: linha a linha isso seria N+1.
        $porRedec = [];

        foreach ($vinculos as $municipioLegadoId => $redecId) {
            if (! $espelho->has($municipioLegadoId)) {
                $report->registrarSkip();

                continue;
            }

            if (! isset($redecsConhecidas[$redecId])) {
                $report->registrarErro($municipioLegadoId, "REDEC {$redecId} nao existe em dec_redecs.");

                continue;
            }

            if ((int) ($espelho->get($municipioLegadoId) ?? 0) === $redecId) {
                $report->registrarSkip();

                continue;
            }

            $porRedec[$redecId][] = $municipioLegadoId;
            $report->registrarAtualizacao();
        }

        if (! $dryRun) {
            foreach ($porRedec as $redecId => $municipios) {
                DB::table('cedec_municipio')
                    ->whereIn('id', $municipios)
                    ->update(['redec_id' => $redecId]);
            }
        }

        return $report;
    }

    /**
     * @return array<int, int> id_municipio legado => id da REDEC
     */
    private function vinculosDoLegado(): array
    {
        $conexao = (string) config('cedec.legacy_connection', 'legado_gestaocedec');

        $linhas = DB::connection($conexao)
            ->table('cedec_rpm_mun')
            ->whereNotNull('id_municipio')
            ->whereNotNull('id_rpm')
            ->where('id_municipio', '!=', self::MUNICIPIO_SENTINELA)
            ->orderBy('id')
            ->get(['id_municipio', 'id_rpm']);

        $vinculos = [];

        // A tabela tem 854 linhas e um id_municipio distinto por linha; o indice por
        // municipio garante que uma duplicidade futura no legado nao gere dois
        // UPDATEs conflitantes -- a ultima linha vence, como no ETL de prefeituras.
        foreach ($linhas as $linha) {
            $vinculos[(int) $linha->id_municipio] = (int) $linha->id_rpm;
        }

        return $vinculos;
    }
}
