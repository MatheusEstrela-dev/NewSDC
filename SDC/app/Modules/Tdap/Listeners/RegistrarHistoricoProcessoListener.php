<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Listeners;

use App\Core\Events\DomainEvent;
use App\Core\Events\IdempotentListener;
use App\Modules\Tdap\Domain\Events\CronogramaAtivadoV1;
use App\Modules\Tdap\Domain\Events\ViagemValidadaV1;
use App\Support\Database\ConexaoDeLog;

/**
 * Registra eventos de dominio em tdap_historicos.
 *
 * Idempotente: cada evento gera no maximo um registro de historico
 * (chave evento_id deduplicada via processed_events).
 *
 * Historico e processed_events usam a mesma transacao na conexao de log.
 * A role dessa conexao precisa acessar tambem processed_events; em banco
 * dedicado, a tabela deve ser provisionada pela migration de idempotencia.
 * Falhas propagam ao worker para retry, sem confirmar consumo prematuramente.
 *
 * O nome fala em "Processo" por heranca: ele nasceu para o modulo de Processos,
 * que saiu, mas continua sendo o unico registrador do historico de CRONOGRAMA
 * ATIVADO e VIAGEM VALIDADA. Apagar junto com o resto teria calado a aba
 * Historico para os dois eventos que ela de fato mostra.
 */
class RegistrarHistoricoProcessoListener extends IdempotentListener
{
    protected function databaseConnection(): ?string
    {
        return ConexaoDeLog::nome();
    }

    protected function execute(DomainEvent $event): void
    {
        $entityType = match (true) {
            $event instanceof CronogramaAtivadoV1 => 'cronograma',
            $event instanceof ViagemValidadaV1    => 'viagem',
            default                               => 'tdap',
        };

        $entityId = $event->aggregateId;
        if ($event instanceof CronogramaAtivadoV1) {
            $entityId = (string) ($event->payload()['cronograma_id'] ?? $event->aggregateId);
        } elseif ($event instanceof ViagemValidadaV1) {
            $entityId = (string) ($event->payload()['viagem_id'] ?? $event->aggregateId);
        }

        $obs = match (true) {
            $event instanceof CronogramaAtivadoV1 => sprintf(
                'Cronograma %s ativado (via outbox).',
                $event->payload()['numero'] ?? '?',
            ),
            $event instanceof ViagemValidadaV1 => 'Viagem validada (via outbox).',
            default => $event->eventName(),
        };

        // Escrita direta: inserir() pode adiar para afterCommit ou engolir
        // falha ao enfileirar, separando o efeito do marcador de idempotencia.
        ConexaoDeLog::tabela('tdap_historicos')->insert([
            'data_evento' => $event->occurredAt->format('Y-m-d H:i:sP'),
            'tipo_evento' => $event->eventName(),
            'entity_type' => $entityType,
            'entity_id'   => (int) (is_numeric($entityId) ? $entityId : 0),
            'user_id'     => $event->metadata['user_id'] ?? null,
            'obs'         => $obs,
            'payload'     => json_encode($event->payload(), JSON_UNESCAPED_UNICODE),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }
}
