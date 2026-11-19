<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\AcompanhamentoService;
use App\Modules\Treinamento\Services\Gestao\GestaoCursoConsulta;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Aba Gestao do curso EAD na Area do Gestor: vagas, periodo, cronograma,
 * historico e cancelamento, ao lado de Conteudo, Provas e Alunos.
 */
class GestaoController extends Controller
{
    public function __construct(
        private readonly GestaoCursoConsulta $consulta,
        private readonly AcompanhamentoService $acompanhamento,
    ) {}

    public function __invoke(Request $request, Treinamento $treinamento): Response
    {
        return Inertia::render('Treinamento/Professor/Gestao', [
            ...$this->consulta->pagina($treinamento, (int) $request->query('historico_pagina', 1)),
            'inscricoes_url' => route('treinamentos.inscricoes.index', $treinamento->id),
            'pendentes' => $this->acompanhamento->contarPendentes($treinamento),
        ]);
    }
}
