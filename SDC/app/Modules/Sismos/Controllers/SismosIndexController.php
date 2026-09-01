<?php

declare(strict_types=1);

namespace App\Modules\Sismos\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sismos\Repositories\SismoRepository;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SismosIndexController extends Controller
{
    public function __construct(
        private readonly SismoRepository $repository,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        // Toda a agregacao ja esta materializada na camada Gold: aqui so se le.
        // Contraste deliberado com InmetIndexController, que hoje busca a API do
        // INMET e calcula media e maxima em PHP a cada request.
        return Inertia::render('Sismos/MapaSismos', [
            'eventos' => $this->repository->mapa()->all(),
            'estatisticas' => $this->repository->estatisticas(),
            'bbox' => config('medalhao.sismos.bbox'),
        ]);
    }
}
