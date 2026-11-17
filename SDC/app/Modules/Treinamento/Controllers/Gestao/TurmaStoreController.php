<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Gestao;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Requests\Gestao\TurmaStoreRequest;
use App\Modules\Treinamento\Services\Gestao\GestaoCursoComandos;

class TurmaStoreController extends Controller
{
    public function __construct(private readonly GestaoCursoComandos $comandos)
    {
    }

    public function __invoke(TurmaStoreRequest $request, Treinamento $treinamento)
    {
        try {
            $this->comandos->criarTurma($treinamento, $request->validated(), $request->user());
        } catch (\DomainException $e) {
            return back()->withErrors(['turma' => $e->getMessage()]);
        }

        return back()->with('success', 'Turma criada.');
    }
}
