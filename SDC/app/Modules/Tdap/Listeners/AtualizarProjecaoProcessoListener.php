<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Listeners;

use App\Core\Events\DomainEvent;
use App\Core\Events\IdempotentListener;
use App\Modules\Tdap\Domain\Events\ProcessoTdapAbertoV1;
use App\Modules\Tdap\Domain\Events\ProcessoTdapTransitadoV1;
use App\Modules\Tdap\Domain\Events\ViagemValidadaV1;
use App\Modules\Tdap\Models\ProcessoTdap;
use Illuminate\Support\Facades\DB;

/**
 * Mantem a read-model tdap_processo_projecoes atualizada.
 *
 * Reage a:
 *  - ProcessoTdapAbertoV1: cria linha na projecao
 *  - ProcessoTdapTransitadoV1: atualiza estado/swimlane + dias_no_estado
 *  - ViagemValidadaV1: incrementa contadores de viagens e m3
 */
class AtualizarProjecaoProcessoListener extends IdempotentListener
{
    protected function execute(DomainEvent $event): void
    {
        match (true) {
            $event instanceof ProcessoTdapAbertoV1     => $this->aoAbrir($event),
            $event instanceof ProcessoTdapTransitadoV1 => $this->aoTransitar($event),
            $event instanceof ViagemValidadaV1         => $this->aoValidarViagem($event),
            default                                    => null,
        };
    }

    private function aoAbrir(ProcessoTdapAbertoV1 $event): void
    {
        $processo = ProcessoTdap::with('municipio:id,nome,uf')->find($event->aggregateId);
        if (! $processo) return;

        DB::table('tdap_processo_projecoes')->upsert([[
            'processo_id'     => $processo->id,
            'numero'          => $processo->numero,
            'estado'          => $processo->estado->value,
            'swimlane_atual'  => $processo->swimlane_atual->value,
            'municipio_id'    => $processo->municipio_id,
            'municipio_nome'  => $processo->municipio?->nome,
            'municipio_uf'    => $processo->municipio?->uf,
            'ultimo_evento'   => $event->eventName(),
            'atualizado_em'   => now(),
        ]], ['processo_id'], ['estado', 'swimlane_atual', 'municipio_nome', 'municipio_uf', 'ultimo_evento', 'atualizado_em']);
    }

    private function aoTransitar(ProcessoTdapTransitadoV1 $event): void
    {
        DB::table('tdap_processo_projecoes')
            ->where('processo_id', $event->aggregateId)
            ->update([
                'estado'               => $event->payload()['estado_novo']    ?? null,
                'swimlane_atual'       => $event->payload()['swimlane_atual'] ?? null,
                'ultimo_evento'        => $event->eventName(),
                'dias_no_estado_atual' => 0,
                'atualizado_em'        => now(),
            ]);
    }

    private function aoValidarViagem(ViagemValidadaV1 $event): void
    {
        $processoId = $event->payload()['processo_id'] ?? null;
        if (! $processoId) return;

        $capacidade = (float) ($event->payload()['capacidade_m3'] ?? 0);

        // incrementEach em vez de DB::raw concatenado: o valor vem do payload de
        // um evento e a versao anterior colava o float direto na string SQL.
        DB::table('tdap_processo_projecoes')
            ->where('processo_id', $processoId)
            ->incrementEach(
                [
                    'total_viagens_validadas' => 1,
                    'total_agua_entregue_m3'  => $capacidade,
                ],
                [
                    'ultimo_evento' => $event->eventName(),
                    'atualizado_em' => now(),
                ],
            );
    }
}
