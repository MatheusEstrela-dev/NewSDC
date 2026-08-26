<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Observers;

use App\Modules\Pmda\Enums\PmdaEventoTipo;
use App\Modules\Pmda\Enums\PmdaStatus;
use App\Modules\Pmda\Models\PmdaPlano;
use App\Modules\Pmda\Models\PmdaPlanoEvento;

class PmdaPlanoObserver
{
    public function creating(PmdaPlano $plano): void
    {
        if (empty($plano->data)) {
            $plano->data = now();
        }
    }

    public function created(PmdaPlano $plano): void
    {
        if (empty($plano->protocolo)) {
            // Formato legado: {id}{YYYYMMDD}
            $plano->protocolo = $plano->id . $plano->data->format('Ymd');
            $plano->saveQuietly();
        }

        // Abre a serie historica pelo mesmo caminho das demais transicoes: a
        // criacao vira LINHA no log e o controller deixa de ter um caso especial.
        PmdaPlanoEvento::registrar(
            $plano,
            PmdaEventoTipo::CRIACAO,
            null,
            PmdaStatus::RASCUNHO,
            $plano->created_by ? (int) $plano->created_by : null,
        );
    }
}
