<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Gestao;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Requests\Gestao\CancelarCursoRequest;
use App\Modules\Treinamento\Services\Gestao\GestaoCursoComandos;

class CancelarController extends Controller
{
    public function __construct(private readonly GestaoCursoComandos $comandos)
    {
    }

    public function __invoke(CancelarCursoRequest $request, Treinamento $treinamento)
    {
        try {
            $this->comandos->cancelarCurso($treinamento, $request->user(), $request->validated('motivo'));
        } catch (\DomainException $e) {
            return back()->withErrors(['cancelar' => $e->getMessage()]);
        }

        return redirect()->route('treinamentos.gestor.index')->with('success', 'Curso cancelado.');
    }
}
