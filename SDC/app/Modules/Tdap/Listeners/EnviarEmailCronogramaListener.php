<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Listeners;

use App\Core\Events\DomainEvent;
use App\Core\Events\IdempotentListener;
use App\Modules\Tdap\Domain\Events\CronogramaAtivadoV1;
use App\Modules\Tdap\Mail\CronogramaAtivadoMail;
use App\Modules\Tdap\Models\Cronograma;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Consumer event-driven do CronogramaAtivadoV1.
 *
 * Substitui o Mail::send sincrono do CronogramaService::ativar pela versao
 * idempotente via outbox.
 */
class EnviarEmailCronogramaListener extends IdempotentListener
{
    protected function execute(DomainEvent $event): void
    {
        if (! $event instanceof CronogramaAtivadoV1) {
            return;
        }

        $cronogramaId = (int) ($event->payload()['cronograma_id'] ?? 0);
        if ($cronogramaId === 0) return;

        $cronograma = Cronograma::with(['prestador', 'municipio', 'ata', 'lote'])->find($cronogramaId);
        if (! $cronograma) {
            Log::warning('Cronograma nao encontrado ao enviar email', [
                'cronograma_id' => $cronogramaId,
                'event_id'      => $event->eventId,
            ]);

            return;
        }

        $mail = new CronogramaAtivadoMail($cronograma);
        $destinatarios = $mail->destinatariosLogicos();
        if (! empty($destinatarios)) {
            Mail::to($destinatarios)->send($mail);
        }
    }
}
