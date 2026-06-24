<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Services;

use App\Modules\Pmda\Enums\PmdaStatus;
use App\Modules\Pmda\Models\PmdaPlano;
use Illuminate\Support\Carbon;

class PmdaCopiaService
{
    private const DATA_MINIMA_COPIA = '2021-04-03';

    public function copiar(PmdaPlano $origem, int $userId): PmdaPlano
    {
        if ($origem->data->lte(Carbon::parse(self::DATA_MINIMA_COPIA))) {
            throw new \DomainException('PMDA anterior a 03/04/2021 não pode ser copiado.');
        }

        if (! $origem->status->permiteCopia()) {
            throw new \DomainException('Status atual não permite cópia.');
        }

        $copia = $origem->replicate(['protocolo', 'status', 'data', 'data_aprov', 'dt_analise']);
        $copia->status     = PmdaStatus::RASCUNHO;
        $copia->data       = now();
        $copia->protocolo  = null; // regerado pelo Observer
        $copia->created_by = $userId;
        $copia->save();

        // Fase 2 estende este service para duplicar comunidades + representantes.
        return $copia;
    }
}
