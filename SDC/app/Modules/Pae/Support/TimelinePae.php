<?php

declare(strict_types=1);

namespace App\Modules\Pae\Support;

use App\Models\User;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Models\PaeTimeline;

/**
 * Registro na timeline do protocolo. Antes estava copiado em PaeProtocoloService
 * e PaeNotificacaoService.
 */
final class TimelinePae
{
    public static function registrar(PaeProtocolo $protocolo, string $evento, string $descricao, User $user): void
    {
        PaeTimeline::create([
            'protocolo_id' => $protocolo->id,
            'evento' => $evento,
            'descricao' => $descricao,
            'user_id' => $user->id,
        ]);
    }
}
