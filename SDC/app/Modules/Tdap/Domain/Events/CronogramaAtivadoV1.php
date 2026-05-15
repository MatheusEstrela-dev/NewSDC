<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Domain\Events;

use App\Core\Events\DomainEvent;

/**
 * Disparado quando um Cronograma e ativado.
 *
 * Substitui o Mail::send sincrono no CronogramaService::ativar pelo padrao
 * event-driven: o servico emite o evento via outbox e EnviarEmailCronogramaListener
 * (IdempotentListener) consome para enviar o email.
 *
 * Consumidores:
 *  - EnviarEmailCronogramaListener (substitui Mail::send no service)
 *  - AtualizarProjecaoListener (refresh dos contadores no read-model)
 *  - RegistrarHistoricoListener
 */
final readonly class CronogramaAtivadoV1 extends DomainEvent
{
    public function eventName(): string
    {
        return 'tdap.cronograma.ativado';
    }

    public function eventVersion(): int
    {
        return 1;
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'cronograma_id' => $this->metadata['cronograma_id'] ?? null,
            'numero'        => $this->metadata['numero']        ?? null,
            'prestador_id'  => $this->metadata['prestador_id']  ?? null,
            'municipio_id'  => $this->metadata['municipio_id']  ?? null,
            'ativado_em'    => $this->metadata['ativado_em']    ?? null,
            'processo_id'   => $this->metadata['processo_id']   ?? null,
        ];
    }
}
