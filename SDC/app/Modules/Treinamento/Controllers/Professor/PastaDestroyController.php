<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Modulo;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\ConteudoCursoService;

class PastaDestroyController extends Controller
{
    public function __construct(private readonly ConteudoCursoService $conteudo) {}

    public function __invoke(Treinamento $treinamento, Modulo $modulo)
    {
        $this->conteudo->garantirDoCurso($treinamento, $modulo);
        $this->conteudo->excluirPasta($modulo);

        return back()->with('success', 'Pasta excluida.');
    }
}
