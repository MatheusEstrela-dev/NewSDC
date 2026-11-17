<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Catalogo;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Resources\CursoResource;
use App\Modules\Treinamento\Services\CatalogoService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CatalogoIndexController extends Controller
{
    public function __construct(private readonly CatalogoService $catalogo)
    {
    }

    public function __invoke(Request $request): Response
    {
        $filtros = $request->only(['busca', 'ativo']);

        return Inertia::render('Treinamento/Catalogo/Index', [
            'cursos' => CursoResource::collection($this->catalogo->listar($filtros, 15)->withPath($request->url())),
            'filtros' => (object) $filtros,
        ]);
    }
}
