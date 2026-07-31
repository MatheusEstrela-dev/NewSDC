<?php

declare(strict_types=1);

namespace App\Modules\Rat\Observers;

use App\Modules\Notificacoes\DTO\NotificacaoSpec;
use App\Modules\Notificacoes\Jobs\EntregarNotificacaoJob;
use App\Modules\Rat\Models\RatOcorrencia;

/**
 * Avisa o autor do RAT quando a ocorrencia e finalizada.
 *
 * O RAT nao tem um fluxo de aprovacao com estados intermediarios: status e um
 * inteiro onde 1 significa Finalizado (ver RatOcorrencia::getStatusLabelAttribute).
 * O aviso vale exatamente na virada para finalizado, porque e o momento em que o
 * autor deixa de poder editar e o relatorio passa a valer.
 */
class RatOcorrenciaNotificacaoObserver
{
    private const STATUS_FINALIZADO = 1;

    public function updated(RatOcorrencia $ocorrencia): void
    {
        if (!$ocorrencia->wasChanged('status')) {
            return;
        }

        if ((int) $ocorrencia->status !== self::STATUS_FINALIZADO) {
            return;
        }

        $autor = $ocorrencia->created_by;

        // Sem autor registrado (importacao de legado) ou o proprio autor
        // finalizando: nao ha novidade a comunicar.
        if ($autor === null || (int) $autor === (int) auth()->id()) {
            return;
        }

        EntregarNotificacaoJob::dispatch(
            new NotificacaoSpec(
                modulo: 'rat',
                titulo: 'RAT finalizado',
                mensagem: sprintf(
                    'A ocorrencia %s foi finalizada e nao aceita mais edicao.',
                    (string) ($ocorrencia->numero_bos ?: "#{$ocorrencia->getKey()}")
                ),
                tipo: 'success',
                // Finalizacao acontece uma vez por ocorrencia: nao ha o que agrupar.
                groupKey: null,
                acaoUrl: "/rat/{$ocorrencia->getKey()}",
                acaoTexto: 'Abrir RAT',
            ),
            [(int) $autor],
        );
    }
}
