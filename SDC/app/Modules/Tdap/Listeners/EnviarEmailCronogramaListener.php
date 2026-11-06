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
 * Confirma o consumo somente apos a fila aceitar o email. A deduplicacao
 * evita novos envios depois de confirmado; queda entre aceite e commit ainda
 * pode repetir a mensagem, pois fila e banco nao compartilham transacao.
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
            // Nao adiar o aceite ate afterCommit: falha da fila deve reverter
            // processed_events para que a entrega do listener possa tentar de novo.
            // O cronograma ja foi confirmado antes do consumo do outbox.
            Mail::to($destinatarios)->queue($mail->beforeCommit());
        }
    }
}
