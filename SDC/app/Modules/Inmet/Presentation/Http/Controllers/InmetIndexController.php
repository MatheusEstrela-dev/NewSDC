<?php

declare(strict_types=1);

namespace App\Modules\Inmet\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inmet\Application\UseCases\GetLeiturasAtuaisUseCase;
use App\Modules\Inmet\Application\UseCases\GetEstatisticasUseCase;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;

class InmetIndexController extends Controller
{
    public function __construct(
        private readonly GetLeiturasAtuaisUseCase $getLeiturasUseCase,
        private readonly GetEstatisticasUseCase $getEstatisticasUseCase,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $uf = $request->input('uf', 'MG');

        $leituras = $this->getLeiturasUseCase->execute($uf);
        $estatisticas = $this->getEstatisticasUseCase->execute($leituras);

        return Inertia::render('Inmet/MapaInmet', [
            'leituras' => $leituras->map(fn($l) => $l->toArray())->values()->all(),
            'estatisticas' => $estatisticas,
            'uf_selecionada' => $uf,
        ]);
    }
}
