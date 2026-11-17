<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Gestao;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Controllers\Gestao\Concerns\EscopoDoCurso;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Models\Turma;
use App\Modules\Treinamento\Services\Gestao\GestaoCursoComandos;
use Illuminate\Http\Request;

class TurmaDestroyController extends Controller
{
    use EscopoDoCurso;

    public function __construct(private readonly GestaoCursoComandos $comandos)
    {
    }

    public function __invoke(Request $request, Treinamento $treinamento, Turma $turma)
    {
        $this->garantirTurmaDoCurso($treinamento, $turma);

        try {
            $this->comandos->removerTurma($turma, $request->user());
        } catch (\DomainException $e) {
            return back()->withErrors(['turma' => $e->getMessage()]);
        }

        return back()->with('success', 'Turma removida.');
    }
}
