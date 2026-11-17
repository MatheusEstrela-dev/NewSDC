<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Catalogo;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Requests\Catalogo\CatalogoStoreRequest;
use App\Modules\Treinamento\Resources\CursoResource;
use App\Modules\Treinamento\Services\CatalogoService;

class CatalogoStoreController extends Controller
{
    public function __construct(private readonly CatalogoService $catalogo)
    {
    }

    public function __invoke(CatalogoStoreRequest $request)
    {
        try {
            $curso = $this->catalogo->criar($request->validated(), $request->user()?->id);
        } catch (\DomainException $e) {
            // A criacao rapida a partir do formulario da turma chama por JSON.
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage(), 'errors' => ['nome' => [$e->getMessage()]]], 422);
            }

            return back()->withErrors(['nome' => $e->getMessage()]);
        }

        if ($request->expectsJson()) {
            return (new CursoResource($curso))->response()->setStatusCode(201);
        }

        return back()->with('success', 'Curso cadastrado no catalogo.');
    }
}
