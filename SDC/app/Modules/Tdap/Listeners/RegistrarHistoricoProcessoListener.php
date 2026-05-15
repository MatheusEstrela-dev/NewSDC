<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Listeners;

use App\Core\Events\DomainEvent;
use App\Core\Events\IdempotentListener;
use App\Modules\Tdap\Domain\Events\CronogramaAtivadoV1;
use App\Modules\Tdap\Domain\Events\ProcessoTdapAbertoV1;
use App\Modules\Tdap\Domain\Events\ProcessoTdapTransitadoV1;
use App\Modules\Tdap\Domain\Events\ViagemValidadaV1;
use Illuminate\Support\Facades\DB;

/**
 * Registra eventos de dominio em tdap_historicos.
 *
 * Idempotente: cada evento gera no maximo um registro de historico
 * (chave evento_id deduplicada via processed_events).
 */
class RegistrarHistoricoProcessoListener extends IdempotentListener
{
    protected function execute(DomainEvent $event): void
    {
        $entityType = match (true) {
            $event instanceof CronogramaAtivadoV1 => 'cronograma',
            $event instanceof ViagemValidadaV1    => 'viagem',
            default                               => 'processo_tdap',
        };

        $entityId = $event->aggregateId;
        if ($event instanceof CronogramaAtivadoV1) {
            $entityId = (string) ($event->payload()['cronograma_id'] ?? $event->aggregateId);
        } elseif ($event instanceof ViagemValidadaV1) {
            $entityId = (string) ($event->payload()['viagem_id'] ?? $event->aggregateId);
        }

        $obs = match (true) {
            $event instanceof ProcessoTdapAbertoV1     => "Processo aberto.",
            $event instanceof ProcessoTdapTransitadoV1 => sprintf(
                'Transitado %s -> %s%s',
                $event->payload()['estado_anterior'] ?? '?',
                $event->payload()['estado_novo']     ?? '?',
                ! empty($event->payload()['motivo']) ? ' ('.$event->payload()['motivo'].')' : '',
            ),
            $event instanceof CronogramaAtivadoV1 => sprintf(
                'Cronograma %s ativado (via outbox).',
                $event->payload()['numero'] ?? '?',
            ),
            $event instanceof ViagemValidadaV1 => 'Viagem validada (via outbox).',
            default => $event->eventName(),
        };

        DB::table('tdap_historicos')->insert([
            'data_evento' => $event->occurredAt->format('Y-m-d H:i:sP'),
            'tipo_evento' => $event->eventName(),
            'entity_type' => $entityType,
            'entity_id'   => (int) (is_numeric($entityId) ? $entityId : 0),
            'user_id'     => $event->metadata['user_id'] ?? $event->payload()['aberto_por'] ?? null,
            'obs'         => $obs,
            'payload'     => json_encode($event->payload(), JSON_UNESCAPED_UNICODE),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }
}
