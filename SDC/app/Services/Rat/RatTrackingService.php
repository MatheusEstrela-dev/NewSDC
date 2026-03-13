<?php

declare(strict_types=1);

namespace App\Services\Rat;

use App\Models\Rat\RatOcorrencia;
use Illuminate\Support\Collection;

/**
 * Rastreamento de status e linha do tempo das ocorrências RAT.
 *
 * Métodos públicos:
 *   getTimeline()         — retorna linha do tempo de uma ocorrência
 *   getOcorrenciasAtivas() — lista ocorrências em rascunho com prazo vencendo
 *   isPrazoVencido()      — verifica se o prazo de edição foi ultrapassado
 */
class RatTrackingService
{
    /**
     * Gera linha do tempo da ocorrência a partir de campos de data.
     */
    public function getTimeline(RatOcorrencia $ocorrencia): array
    {
        $timeline = [];

        $timeline[] = [
            'evento'  => 'Ocorrência criada',
            'data'    => $ocorrencia->created_at?->toIso8601String(),
            'usuario' => $ocorrencia->created_by,
        ];

        if ($ocorrencia->isFinalizado()) {
            $timeline[] = [
                'evento'  => 'Ocorrência finalizada',
                'data'    => $ocorrencia->updated_at?->toIso8601String(),
                'usuario' => $ocorrencia->updated_by,
            ];
        }

        return $timeline;
    }

    /**
     * Retorna ocorrências em rascunho cujo prazo de edição está vencendo
     * (nas próximas $horasAlerta horas ou já vencido).
     */
    public function getOcorrenciasAtivas(int $horasAlerta = 24): Collection
    {
        return RatOcorrencia::where('status', 0)
            ->where('prazo_edicao', '<=', now()->addHours($horasAlerta))
            ->orderBy('prazo_edicao')
            ->get();
    }

    /** Verifica se o prazo de edição de uma ocorrência já foi ultrapassado. */
    public function isPrazoVencido(RatOcorrencia $ocorrencia): bool
    {
        return $ocorrencia->prazo_edicao !== null
            && $ocorrencia->prazo_edicao->isPast();
    }
}
