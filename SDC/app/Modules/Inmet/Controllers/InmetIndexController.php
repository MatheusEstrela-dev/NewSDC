<?php

declare(strict_types=1);

namespace App\Modules\Inmet\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inmet\Services\InmetService;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;

class InmetIndexController extends Controller
{
    public function __construct(
        private readonly InmetService $inmetService,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $uf = $request->input('uf', 'MG');

        $leituras = $this->inmetService->getLeiturasAtuais($uf);
        $estatisticas = $this->inmetService->getEstatisticas($leituras);

        return Inertia::render('Inmet/MapaInmet', [
            'leituras' => $leituras->map(fn($l) => $l->toArray())->values()->all(),
            'estatisticas' => $estatisticas,
            'uf_selecionada' => $uf,
        ]);
    }
}
