<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Modulo;
use App\Modules\Treinamento\Models\ModuloItem;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Models\Video;
use App\Modules\Treinamento\Services\AcompanhamentoService;
use App\Modules\Treinamento\Support\PermissaoDeVideo;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConteudoController extends Controller
{
    public function __construct(
        private readonly AcompanhamentoService $acompanhamento,
        private readonly PermissaoDeVideo $permissaoDeVideo,
    ) {}

    public function __invoke(Request $request, Treinamento $treinamento): Response
    {
        $pastas = Modulo::query()
            ->porTreinamento($treinamento->id)
            ->ordenados()
            ->with([
                'itens.video' => fn ($query) => $query->withCount('itens as usos'),
                'itens.video.uploadAberto',
                'itens.prova',
            ])
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
                    'video' => $i->video ? $this->videoDoItem($i->video, $request) : null,
                    'prova_id' => $i->prova?->id,
                    'nota_minima' => $i->prova?->nota_minima,
                    'max_tentativas' => $i->prova?->max_tentativas,
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
            'pendentes' => $this->acompanhamento->contarPendentes($treinamento),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function videoDoItem(Video $video, Request $request): array
    {
        $upload = $video->uploadAberto;

        return [
            'id' => $video->id,
            'titulo' => $video->titulo,
            'status' => $video->status->value,
            'status_label' => $video->status->getLabel(),
            'duracao_segundos' => $video->duracao_segundos,
            'erro' => $video->erro,
            'usos' => (int) $video->usos,
            'tem_versao' => $video->temVersaoPublicada(),
            'pode_gerenciar' => $this->permissaoDeVideo->podeGerenciar($video, $request->user()),
            'upload_aberto' => $upload ? [
                'id' => $upload->id,
                'partes_recebidas' => $upload->partes_recebidas,
                'total_partes' => $upload->total_partes,
            ] : null,
        ];
    }
}
