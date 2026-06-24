<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Services;

use App\Modules\Pmda\Models\PmdaPlano;
use App\Modules\Pmda\Models\PmdaPonto;
use Illuminate\Support\Collection;

class PlanoPontoService
{
    public function vincular(PmdaPlano $plano, int $pontoId): void
    {
        // syncWithoutDetaching evita duplicar o vinculo (unique no pivot).
        $plano->pontos()->syncWithoutDetaching([$pontoId]);
    }

    public function desvincular(PmdaPlano $plano, int $pontoId): void
    {
        $plano->pontos()->detach($pontoId);
    }

    /** Pontos ativos do municipio do plano disponiveis para vinculo. */
    public function disponiveis(PmdaPlano $plano): Collection
    {
        return PmdaPonto::query()
            ->where('municipio_id', $plano->municipio_id)
            ->where('ativo', true)
            ->orderBy('nome')
            ->get();
    }
}
