<?php

declare(strict_types=1);

namespace App\Modules\Pae\Support;

use Illuminate\Support\Facades\DB;

/**
 * Backfill dos protocolos sem data da notificacao da FEAM (decisao da CEDEC de
 * 2026-10-02): a data e estimada e marcada como estimada, para a CEDEC corrigir.
 *
 *   - com limite_analise legado: FEAM = limite - 300 (preserva o prazo que ja existia);
 *   - sem limite: FEAM = dt_entrada.
 *
 * Depois recalcula limite_analise pela mesma regra do PrazoAnalise. So usa
 * query builder e a classe pura, para poder rodar dentro de migration.
 * Idempotente: so toca linhas com dt_notificacao_feam nulo.
 */
final class EstimativaNotificacaoFeam
{
    public static function aplicar(): int
    {
        $total = 0;

        DB::table('pae_protocolos')
            ->whereNull('dt_notificacao_feam')
            ->whereNotNull('dt_entrada')
            ->orderBy('id')
            ->chunkById(200, function ($lote) use (&$total): void {
                foreach ($lote as $protocolo) {
                    $feam = $protocolo->limite_analise !== null
                        ? Datas::dia($protocolo->limite_analise)->subDays(PrazoAnalise::PRAZO_DIAS)
                        : Datas::dia($protocolo->dt_entrada);

                    $notificacoes = DB::table('pae_notificacoes as n')
                        ->join('pae_analises as a', 'a.id', '=', 'n.pae_analise_id')
                        ->where('a.pae_protocolo_id', $protocolo->id)
                        ->whereNull('n.deleted_at')
                        ->orderBy('n.dt_notificacao')
                        ->orderBy('n.id')
                        ->get(['n.dt_notificacao', 'n.dt_devolutiva'])
                        ->map(fn ($n): array => ['dt_notificacao' => $n->dt_notificacao, 'dt_devolutiva' => $n->dt_devolutiva])
                        ->all();

                    $limite = PrazoAnalise::limite($feam, PrazoAnalise::intervalosDeNotificacoes($notificacoes));

                    DB::table('pae_protocolos')->where('id', $protocolo->id)->update([
                        'dt_notificacao_feam' => $feam->toDateString(),
                        'dt_notificacao_feam_estimada' => true,
                        'limite_analise' => $limite?->toDateString(),
                    ]);
                    $total++;
                }
            });

        return $total;
    }
}
