<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Observers;

use App\Modules\Pmda\Models\PmdaPlano;

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
    }
}
