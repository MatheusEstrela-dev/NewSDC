<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Gestao;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Enums\TipoEventoCronograma;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\Gestao\GestaoCursoConsulta;
use App\Modules\Treinamento\Services\TreinamentoService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pagina do curso na Area do Gestor: resumo, turmas, previa do cronograma e
 * historico paginado. Turmas/cronograma/cancelamento sao escritos pelas
 * rotas de comando (ver GestaoCursoComandos); esta tela so le.
 */
class ShowController extends Controller
{
    public function __construct(
        private readonly GestaoCursoConsulta $consulta,
        private readonly TreinamentoService $treinamentoService,
    ) {
    }

    public function __invoke(Request $request, Treinamento $treinamento): Response
    {
        $paginaHistorico = max(1, (int) $request->query('historico_pagina', 1));

        return Inertia::render('Treinamento/Gestao/Show', [
            'curso' => $this->consulta->resumo($treinamento),
            'turmas' => $this->consulta->turmas($treinamento),
            'historico' => $this->consulta->historico($treinamento, 15, $paginaHistorico),
            'tipoEventoCronogramaOptions' => TipoEventoCronograma::toSelectArray(),
            'municipios' => $this->treinamentoService->municipiosDeMg(),
            // Destinos que a gestao nao duplica: conteudo/alunos continuam nas
            // telas do professor (EAD), inscricoes na tela de admin de sempre.
            'links' => [
                'conteudo' => $treinamento->isEad() ? route('treinamentos.professor.conteudo', $treinamento->id) : null,
                'alunos' => $treinamento->isEad() ? route('treinamentos.professor.alunos', $treinamento->id) : null,
                'inscricoes' => route('treinamentos.inscricoes.index', $treinamento->id),
                'detalhes' => route('treinamentos.show', $treinamento->id),
            ],
        ]);
    }
}
