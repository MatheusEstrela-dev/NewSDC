<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\ProfessorService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MeusCursosController extends Controller
{
    public function __construct(private readonly ProfessorService $professores) {}

    public function __invoke(Request $request): Response
    {
        $cursos = $this->professores->cursosDe((int) $request->user()->getKey())
            ->loadCount('inscricoesAprovadas')
            ->map(fn (Treinamento $t) => [
                'id' => $t->id,
                'titulo' => $t->titulo,
                'publico_alvo_label' => $t->publico_alvo?->getLabel(),
                'status' => $t->status->value,
                'status_label' => $t->status->getLabel(),
                'publicado' => $t->estaPublicado(),
                'situacao' => $t->situacaoEfetiva()->value,
                'situacao_label' => $t->situacaoEfetiva()->getLabel(),
                'alunos' => (int) $t->inscricoes_aprovadas_count,
            ])
            ->values();

        return Inertia::render('Treinamento/Professor/MeusCursos', [
            'cursos' => $cursos,
            // Abrir curso e so de quem ja cria treinamento (admin); o backend recusa os demais.
            'pode_abrir' => (bool) $request->user()->can('treinamento.cursos.create'),
        ]);
    }
}
