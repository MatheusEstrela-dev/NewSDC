<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Catalogo;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Curso;
use App\Modules\Treinamento\Requests\Catalogo\CatalogoUpdateRequest;
use App\Modules\Treinamento\Services\CatalogoService;

class CatalogoUpdateController extends Controller
{
    public function __construct(private readonly CatalogoService $catalogo)
    {
    }

    public function __invoke(CatalogoUpdateRequest $request, Curso $curso)
    {
        try {
            $this->catalogo->atualizar($curso, $request->validated());
        } catch (\DomainException $e) {
            return back()->withErrors(['nome' => $e->getMessage()]);
        }

        return back()->with('success', 'Curso atualizado.');
    }
}
