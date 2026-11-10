<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Requests\Professor\SalvarPastaRequest;
use App\Modules\Treinamento\Services\ConteudoCursoService;

class PastaStoreController extends Controller
{
    public function __construct(private readonly ConteudoCursoService $conteudo) {}

    public function __invoke(SalvarPastaRequest $request, Treinamento $treinamento)
    {
        $this->conteudo->criarPasta($treinamento, $request->validated());

        return back()->with('success', 'Pasta criada.');
    }
}
