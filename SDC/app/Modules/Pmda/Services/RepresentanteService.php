<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Services;

use App\Modules\Pmda\Models\PmdaComunidade;
use App\Modules\Pmda\Models\PmdaRepresentante;

class RepresentanteService
{
    public function __construct(private readonly PmdaPlanoService $planos) {}

    public function adicionar(PmdaComunidade $comunidade, array $data): PmdaRepresentante
    {
        $representante = $comunidade->representantes()->create($data);
        $this->recalcular($comunidade);

        return $representante;
    }

    public function atualizar(PmdaRepresentante $representante, array $data): PmdaRepresentante
    {
        $representante->update($data);

        return $representante->refresh();
    }

    public function remover(PmdaRepresentante $representante): void
    {
        $comunidade = $representante->comunidade;
        $representante->delete();
        if ($comunidade) {
            $this->recalcular($comunidade);
        }
    }

    private function recalcular(PmdaComunidade $comunidade): void
    {
        $plano = $comunidade->plano;
        if ($plano) {
            $this->planos->recalcularStatus($plano);
        }
    }
}
