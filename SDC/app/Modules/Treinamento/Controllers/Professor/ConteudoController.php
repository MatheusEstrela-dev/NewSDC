<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Modulo;
use App\Modules\Treinamento\Models\ModuloItem;
use App\Modules\Treinamento\Models\Treinamento;
use Inertia\Inertia;
use Inertia\Response;

class ConteudoController extends Controller
{
    public function __invoke(Treinamento $treinamento): Response
    {
        $pastas = Modulo::query()
            ->porTreinamento($treinamento->id)
            ->ordenados()
            ->with('itens')
            ->get()
            ->map(fn (Modulo $m) => [
                'id' => $m->id,
                'titulo' => $m->titulo,
                'descricao' => $m->descricao,
                'carga_horaria' => $m->carga_horaria,
                'itens' => $m->itens->map(fn (ModuloItem $i) => [
                    'id' => $i->id,
                    'tipo' => $i->tipo->value,
                    'tipo_label' => $i->tipo->getLabel(),
                    'titulo' => $i->titulo,
                    'descricao' => $i->descricao,
                    'obrigatorio' => $i->obrigatorio,
                    'total_paginas' => $i->total_paginas,
                    'tem_arquivo' => $i->temArquivo(),
                    'arquivo_nome' => $i->arquivo_nome,
                ])->values(),
            ])
            ->values();

        return Inertia::render('Treinamento/Professor/Conteudo', [
            'curso' => [
                'id' => $treinamento->id,
                'titulo' => $treinamento->titulo,
                'carga_horaria' => $treinamento->carga_horaria,
                'publicado' => $treinamento->estaPublicado(),
            ],
            'pastas' => $pastas,
        ]);
    }
}
