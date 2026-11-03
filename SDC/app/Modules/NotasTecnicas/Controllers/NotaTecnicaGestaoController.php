<?php

declare(strict_types=1);

namespace App\Modules\NotasTecnicas\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\NotasTecnicas\DTOs\NotaTecnicaDTO;
use App\Modules\NotasTecnicas\DTOs\NotaTecnicaVersaoDTO;
use App\Modules\NotasTecnicas\Models\NotaTecnica;
use App\Modules\NotasTecnicas\Requests\StoreNotaTecnicaRequest;
use App\Modules\NotasTecnicas\Requests\UpdateNotaTecnicaRequest;
use App\Modules\NotasTecnicas\Resources\NotaTecnicaDetalheResource;
use App\Modules\NotasTecnicas\Services\NotaTecnicaCategoriaService;
use App\Modules\NotasTecnicas\Services\NotaTecnicaConsultaService;
use App\Modules\NotasTecnicas\Services\NotaTecnicaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Cadastro e ciclo de vida da nota: criar (rascunho + primeiro PDF), editar os
 * dados, publicar, revogar e excluir. Permissao de cada acao no middleware da rota.
 */
final class NotaTecnicaGestaoController extends Controller
{
    public function __construct(
        private readonly NotaTecnicaService $notas,
        private readonly NotaTecnicaCategoriaService $categorias,
        private readonly NotaTecnicaConsultaService $consulta,
    ) {}

    public function create(Request $request): Response
    {
        return Inertia::render('NotasTecnicas/Create', [
            ...$this->opcoesDoFormulario(),
            'categoriaSugerida' => filter_var($request->query('categoria_id'), FILTER_VALIDATE_INT) ?: null,
        ]);
    }

    public function store(StoreNotaTecnicaRequest $request): RedirectResponse
    {
        $nota = $this->notas->criar(
            NotaTecnicaDTO::fromRequest($request->validated()),
            NotaTecnicaVersaoDTO::fromRequest($request->validated()),
            $request->user(),
        );

        return redirect()
            ->route('notas-tecnicas.edit', $nota)
            ->with('success', "Nota {$nota->codigo} cadastrada como rascunho. Publique quando estiver pronta.");
    }

    public function edit(Request $request, NotaTecnica $nota): Response
    {
        return Inertia::render('NotasTecnicas/Edit', [
            ...$this->opcoesDoFormulario(),
            'nota' => (new NotaTecnicaDetalheResource(
                $this->consulta->detalhe($nota, null, $request->user()),
            ))->resolve($request),
            'valores' => [
                'titulo' => $nota->titulo,
                'categoria_id' => $nota->categoria_id,
                'resumo' => $nota->resumo,
                'vigencia_inicio' => $nota->vigencia_inicio?->toDateString(),
                'rotas_relacionadas' => array_column($nota->rotas_relacionadas ?? [], 'nome'),
            ],
            'proximaVersao' => $this->notas->sugerirProximaVersao($nota),
            'permissoes' => [
                'publicar' => $request->user()->can('notas_tecnicas.notas.publicar'),
                'excluir' => $request->user()->can('notas_tecnicas.notas.delete'),
                'leituras' => $request->user()->can('notas_tecnicas.leituras.view'),
            ],
        ]);
    }

    public function update(UpdateNotaTecnicaRequest $request, NotaTecnica $nota): RedirectResponse
    {
        $this->notas->atualizar($nota, NotaTecnicaDTO::fromRequest($request->validated()));

        return back()->with('success', 'Dados da nota atualizados.');
    }

    public function publicar(Request $request, NotaTecnica $nota): RedirectResponse
    {
        $this->notas->publicar($nota, $request->user());

        return back()->with('success', "Nota {$nota->codigo} publicada.");
    }

    public function revogar(NotaTecnica $nota): RedirectResponse
    {
        $this->notas->revogar($nota);

        return back()->with('success', "Nota {$nota->codigo} revogada. Ela segue consultável como obsoleta.");
    }

    public function destroy(NotaTecnica $nota): RedirectResponse
    {
        $this->notas->excluir($nota);

        return redirect()
            ->route('notas-tecnicas.index')
            ->with('success', "Nota {$nota->codigo} excluída.");
    }

    /** @return array<string, mixed> */
    private function opcoesDoFormulario(): array
    {
        return [
            'categorias' => $this->categorias->opcoes(),
            'telas' => collect(config('notas_tecnicas.telas_relacionadas', []))
                ->filter(fn (string $rotulo, string $rota): bool => Route::has($rota))
                ->map(fn (string $rotulo, string $rota): array => ['value' => $rota, 'label' => $rotulo])
                ->values()
                ->all(),
            'limiteMb' => (int) round(config('notas_tecnicas.upload_max_bytes') / 1024 / 1024),
        ];
    }
}
