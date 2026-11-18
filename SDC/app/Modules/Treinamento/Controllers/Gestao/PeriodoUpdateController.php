<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Gestao;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Requests\Gestao\PeriodoUpdateRequest;
use App\Modules\Treinamento\Services\Gestao\GestaoCursoComandos;

class PeriodoUpdateController extends Controller
{
    public function __construct(private readonly GestaoCursoComandos $comandos)
    {
    }

    public function __invoke(PeriodoUpdateRequest $request, Treinamento $treinamento)
    {
        try {
            $this->comandos->definirPeriodoDoCurso(
                $treinamento,
                $request->validated('data_inicio'),
                $request->validated('data_fim'),
                $request->user(),
            );
        } catch (\DomainException $e) {
            return back()->withErrors(['periodo' => $e->getMessage()]);
        }

        return back()->with('success', 'Periodo do curso atualizado.');
    }
}
