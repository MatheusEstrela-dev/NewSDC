<?php

declare(strict_types=1);

namespace App\Modules\Rat\Services;

use App\Modules\Rat\Models\RatOcorrencia;
use App\Modules\Rat\Models\RatOcorrenciaHistorico;

class RatHistoricoService
{
    public function timeline(int $id, int $porPagina = 20): array
    {
        $items = RatOcorrenciaHistorico::where('ocorrencia_id', $id)
            ->orderByDesc('created_at')
            ->paginate($porPagina);

        return [
            'data'  => $items->items(),
            'total' => $items->total(),
            'page'  => $items->currentPage(),
        ];
    }

    public function recent(int $id, int $limite = 10): array
    {
        return RatOcorrenciaHistorico::where('ocorrencia_id', $id)
            ->orderByDesc('created_at')
            ->limit($limite)
            ->get()
            ->toArray();
    }

    public function log(RatOcorrencia $ocorrencia, string $evento, array $payload = []): void
    {
        RatOcorrenciaHistorico::create([
            'ocorrencia_id' => $ocorrencia->id,
            'user_id'       => auth()->id(),
            'evento'        => $evento,
            'payload'       => $payload,
            'ip_address'    => request()->ip(),
            'user_agent'    => request()->userAgent(),
        ]);
    }
}
