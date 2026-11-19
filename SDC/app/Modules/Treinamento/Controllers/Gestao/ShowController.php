<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Gestao;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\Gestao\GestaoCursoConsulta;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Gestao do curso aberta pelo detalhe do curso, para as modalidades que nao
 * tem Area do Gestor (presencial, semipresencial, online). Curso EAD e
 * gerido pela aba Gestao da Area do Gestor (Professor\GestaoController).
 * Cronograma/cancelamento sao escritos pelas rotas de comando (ver
 * GestaoCursoComandos); esta tela so le.
 */
class ShowController extends Controller
{
    public function __construct(private readonly GestaoCursoConsulta $consulta)
    {
    }

    public function __invoke(Request $request, Treinamento $treinamento): Response
    {
        return Inertia::render('Treinamento/Gestao/Show', [
            ...$this->consulta->pagina($treinamento, (int) $request->query('historico_pagina', 1)),
            'links' => [
                'inscricoes' => route('treinamentos.inscricoes.index', $treinamento->id),
                'detalhes' => route('treinamentos.show', $treinamento->id),
            ],
        ]);
    }
}
