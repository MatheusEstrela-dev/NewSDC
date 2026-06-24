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

        $this->duplicarComunidades($origem, $copia);

        return $copia->refresh();
    }

    private function duplicarComunidades(PmdaPlano $origem, PmdaPlano $copia): void
    {
        foreach ($origem->comunidades()->with('representantes')->get() as $comunidade) {
            $novaComunidade = $comunidade->replicate(['pmda_plano_id']);
            $novaComunidade->pmda_plano_id = $copia->id;
            $novaComunidade->save();

            foreach ($comunidade->representantes as $representante) {
                $novoRepresentante = $representante->replicate(['pmda_comunidade_id']);
                $novoRepresentante->pmda_comunidade_id = $novaComunidade->id;
                $novoRepresentante->save();
            }
        }
    }
}
