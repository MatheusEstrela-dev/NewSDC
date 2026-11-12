<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Listeners;

use App\Modules\Treinamento\Events\ItemDaTrilhaConcluido;

/**
 * Avalia se a inscricao concluiu o curso a cada item concluido (spec 8.4):
 * congela concluido_em/aproveitamento na inscricao e emite o certificado.
 *
 * Registrado em App\Providers\EventServiceProvider (o provider do modulo nao
 * registra listeners). Sem efeito ate a implementacao da conclusao por trilha.
 */
final class AvaliarConclusaoDaInscricao
{
    public function handle(ItemDaTrilhaConcluido $evento): void
    {
        //
    }
}
