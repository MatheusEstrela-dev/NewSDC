<?php

declare(strict_types=1);

namespace App\Modules\Inmet\Application\UseCases;

use Illuminate\Support\Collection;

class GetEstatisticasUseCase
{
    public function execute(Collection $leituras): array
    {
        if ($leituras->isEmpty()) {
            return [
                'total_estacoes' => 0,
                'precipitacao_media' => 0,
                'precipitacao_maxima' => 0,
                'estacoes_com_chuva' => 0,
                'temperatura_media' => 0,
            ];
        }

        return [
            'total_estacoes' => $leituras->count(),
            'precipitacao_media' => round($leituras->avg('precipitacao') ?? 0, 2),
            'precipitacao_maxima' => round($leituras->max('precipitacao') ?? 0, 2),
            'estacoes_com_chuva' => $leituras->where('precipitacao', '>', 0)->count(),
            'temperatura_media' => round($leituras->avg('temperatura') ?? 0, 2),
        ];
    }
}
