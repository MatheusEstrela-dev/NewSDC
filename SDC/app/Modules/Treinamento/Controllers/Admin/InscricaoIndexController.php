<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Enums\StatusInscricao;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Resources\InscricaoResource;
use App\Modules\Treinamento\Services\InscricaoService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InscricaoIndexController extends Controller
{
    public function __construct(
        private readonly InscricaoService $inscricaoService
    ) {
    }

    public function __invoke(Request $request, Treinamento $treinamento): Response
    {
        $filters = $request->only(['status']);
        $inscricoes = $this->inscricaoService->listPorTreinamento($treinamento->id, $filters, 15);

        return Inertia::render('Treinamento/Inscricoes/Index', [
            'treinamento' => [
                'id' => $treinamento->id,
                'titulo' => $treinamento->titulo,
                'presenca_liberada' => (bool) $treinamento->presenca_liberada,
                'modulos' => $treinamento->modulos()->orderBy('ordem')->get(['id', 'titulo'])->map(fn ($m) => [
                    'id' => $m->id,
                    'titulo' => $m->titulo,
                ]),
            ],
            'inscricoes' => InscricaoResource::collection($inscricoes->withPath($request->url())),
            'filters' => $filters,
            'filterOptions' => [
                'status' => StatusInscricao::toSelectArray(),
            ],
        ]);
    }
}
