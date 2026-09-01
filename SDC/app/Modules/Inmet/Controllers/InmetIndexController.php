<?php

declare(strict_types=1);

namespace App\Modules\Inmet\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inmet\Repositories\InmetRepository;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InmetIndexController extends Controller
{
    public function __construct(
        private readonly InmetRepository $repository,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        // Toda a agregacao ja esta materializada na camada Gold: aqui so se le.
        //
        // O parametro uf saiu de proposito: o recorte e do pipeline, nao da
        // requisicao. E a bbox aqui nao filtra nada — serve para o mapa se
        // enquadrar em MG.
        return Inertia::render('Inmet/MapaInmet', [
            'estacoes' => $this->repository->mapa()->all(),
            'estatisticas' => $this->repository->estatisticas(),
            'bbox' => config('medalhao.inmet.bbox'),
        ]);
    }
}
