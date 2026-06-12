<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tdap\DTOs\CaminhaoDTO;
use App\Modules\Tdap\Models\Caminhao;
use App\Modules\Tdap\Models\Prestador;
use App\Modules\Tdap\Requests\StoreCaminhaoRequest;
use App\Modules\Tdap\Requests\UpdateCaminhaoRequest;
use App\Modules\Tdap\Resources\CaminhaoIndexResource;
use App\Modules\Tdap\Resources\CaminhaoResource;
use App\Modules\Tdap\Services\CaminhaoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CaminhaoController extends Controller
{
    public function __construct(
        private readonly CaminhaoService $service,
    ) {}

    public function index(Request $request): Response
    {
        $perPage = (int) $request->integer('per_page', 15);
        $filtros = $request->only(['ativo', 'prestador_id', 'search']);

        $caminhoes = $this->service->listar($perPage, $filtros);

        return Inertia::render('Tdap/Caminhoes/Index', [
            'caminhoes'    => CaminhaoIndexResource::collection($caminhoes),
            'estatisticas' => fn () => $this->service->obterEstatisticas(),
            'prestadores'  => fn () => Prestador::ativo()->orderBy('nome')->get(['id', 'nome', 'cnpj']),
            'filtros'      => $filtros,
            'canCreate'    => $request->user()?->can('tdap.caminhoes.create') ?? false,
            'canEdit'      => $request->user()?->can('tdap.caminhoes.edit') ?? false,
            'canDelete'    => $request->user()?->can('tdap.caminhoes.delete') ?? false,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filtros = $request->only(['ativo', 'prestador_id', 'search']);
        $data = $this->service->exportar($filtros);

        $filename = 'caminhoes_'.now()->format('Y-m-d_H-i-s').'.csv';

        return response()->streamDownload(function () use ($data): void {
            $handle = fopen('php://output', 'w');

            // BOM UTF-8 para Excel reconhecer acentuacao.
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            if (! empty($data)) {
                fputcsv($handle, array_keys($data[0]), ';');
            }

            foreach ($data as $row) {
                fputcsv($handle, array_values($row), ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Tdap/Caminhoes/Create', [
            'prestadores' => Prestador::ativo()->orderBy('nome')->get(['id', 'nome', 'cnpj']),
        ]);
    }

    public function store(StoreCaminhaoRequest $request): RedirectResponse
    {
        $caminhao = $this->service->criar(
            CaminhaoDTO::fromRequest($request->validated()),
        );

        return redirect()
            ->route('tdap.caminhoes.show', $caminhao->id)
            ->with('success', "Caminhão {$caminhao->placa} cadastrado.");
    }

    public function show(Caminhao $caminhao, Request $request): Response
    {
        $caminhao = $this->service->obter($caminhao->id);

        return Inertia::render('Tdap/Caminhoes/Show', [
            'caminhao'  => CaminhaoResource::make($caminhao),
            'canEdit'   => $request->user()?->can('tdap.caminhoes.edit') ?? false,
            'canDelete' => $request->user()?->can('tdap.caminhoes.delete') ?? false,
        ]);
    }

    public function edit(Caminhao $caminhao): Response
    {
        return Inertia::render('Tdap/Caminhoes/Edit', [
            'caminhao'    => CaminhaoResource::make($caminhao->load('prestador')),
            'prestadores' => Prestador::ativo()->orderBy('nome')->get(['id', 'nome', 'cnpj']),
        ]);
    }

    public function update(UpdateCaminhaoRequest $request, Caminhao $caminhao): RedirectResponse
    {
        $atualizado = $this->service->atualizar(
            $caminhao->id,
            CaminhaoDTO::fromRequest($request->validated()),
        );

        return redirect()
            ->route('tdap.caminhoes.show', $atualizado->id)
            ->with('success', "Caminhão {$atualizado->placa} atualizado.");
    }

    public function destroy(Caminhao $caminhao, Request $request): RedirectResponse
    {
        if (! $request->user()?->can('tdap.caminhoes.delete')) {
            abort(403);
        }

        $placa = $caminhao->placa;
        $this->service->deletar($caminhao->id);

        return redirect()
            ->route('tdap.caminhoes.index')
            ->with('success', "Caminhão {$placa} excluído.");
    }
}
