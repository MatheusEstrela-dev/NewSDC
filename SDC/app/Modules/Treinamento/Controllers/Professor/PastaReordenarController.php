<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Requests\Professor\ReordenarRequest;
use App\Modules\Treinamento\Services\ConteudoCursoService;

class PastaReordenarController extends Controller
{
    public function __construct(private readonly ConteudoCursoService $conteudo) {}

    public function __invoke(ReordenarRequest $request, Treinamento $treinamento)
    {
        try {
            $this->conteudo->reordenarPastas($treinamento, $request->validated('ids'));
        } catch (\DomainException $e) {
            return back()->withErrors(['ids' => $e->getMessage()]);
        }

        return back();
    }
}
