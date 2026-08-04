<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Application\Sagas;

use App\Core\Events\DomainEvent;
use App\Core\Events\IdempotentListener;
use App\Core\Outbox\OutboxDispatcher;
use App\Modules\Tdap\Domain\Events\ExecucaoConcluidaV1;
use App\Modules\Tdap\Domain\Events\ViagemValidadaV1;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Saga de encerramento da execucao.
 *
 * Quando todas as viagens previstas dos cronogramas vinculados a um
 * ProcessoTdap forem validadas, emite ExecucaoConcluidaV1 (que dispara
 * a transicao EM_EXECUCAO -> LIQUIDACAO_PENDENTE).
 *
 * Estado parcial persistido em tdap_sagas.
 */
class EncerramentoSaga extends IdempotentListener
{
    public function __construct(
        private readonly OutboxDispatcher $outbox,
    ) {}

    protected function execute(DomainEvent $event): void
    {
        if (! $event instanceof ViagemValidadaV1) {
            return;
        }

        $processoId = (string) ($event->payload()['processo_id'] ?? '');
        if ($processoId === '') return;

        // Carrega ou cria estado parcial da saga
        $saga = DB::table('tdap_sagas')
            ->where('saga_class', static::class)
            ->where('correlation_id', $processoId)
            ->lockForUpdate()
            ->first();

        if (! $saga) {
            DB::table('tdap_sagas')->insert([
                'id'              => (string) Str::uuid7(),
                'saga_class'      => static::class,
                'correlation_id'  => $processoId,
                'estado_saga'     => 'iniciada',
                'estado_acumulado' => json_encode(['viagens_validadas' => 0]),
                'iniciada_em'     => now(),
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
            $acumulado = ['viagens_validadas' => 0];
        } else {
            $acumulado = json_decode($saga->estado_acumulado, true) ?: ['viagens_validadas' => 0];
        }

        $acumulado['viagens_validadas'] = ($acumulado['viagens_validadas'] ?? 0) + 1;

        // Calcula totais agregados dos cronogramas vinculados ao ProcessoTdap
        $totais = $this->totaisDoProcesso($processoId);
        $devePassarParaLiquidacao = $totais['previstas'] > 0
            && $acumulado['viagens_validadas'] >= $totais['previstas'];

        DB::table('tdap_sagas')
            ->where('saga_class', static::class)
            ->where('correlation_id', $processoId)
            ->update([
                'estado_acumulado' => json_encode($acumulado),
                'estado_saga'      => $devePassarParaLiquidacao ? 'completada' : 'em_andamento',
                'completada_em'    => $devePassarParaLiquidacao ? now() : null,
                'updated_at'       => now(),
            ]);

        if ($devePassarParaLiquidacao) {
            $this->outbox->persist(new ExecucaoConcluidaV1(
                eventId:       DomainEvent::newId(),
                aggregateType: 'processo_tdap',
                aggregateId:   $processoId,
                occurredAt:    new \DateTimeImmutable(),
                metadata: [
                    'processo_id'             => $processoId,
                    'total_viagens_validadas' => $acumulado['viagens_validadas'],
                    'total_agua_entregue_m3'  => $totais['agua_entregue'],
                ],
            ));
        }
    }

    /**
     * Soma num_viagens previstas e agua_entregue dos CronoCaminhoes
     * vinculados aos Cronogramas do ProcessoTdap. Resultado dispara a
     * transicao EM_EXECUCAO -> LIQUIDACAO_PENDENTE quando todas as viagens
     * previstas estao validadas.
     *
     * @return array{previstas: int, agua_entregue: float}
     */
    private function totaisDoProcesso(string $processoId): array
    {
        $row = DB::table('tdap_crono_caminhoes as cc')
            ->join('tdap_cronogramas as c', 'c.id', '=', 'cc.cronograma_id')
            ->where('c.processo_tdap_id', $processoId)
            ->whereNull('cc.deleted_at')
            ->whereNull('c.deleted_at')
            ->selectRaw('
                COALESCE(SUM(cc.num_viagens), 0)   as previstas,
                COALESCE(SUM(cc.agua_entregue), 0) as agua_entregue
            ')
            ->first();

        return [
            'previstas'     => (int) ($row->previstas ?? 0),
            'agua_entregue' => (float) ($row->agua_entregue ?? 0),
        ];
    }
}
