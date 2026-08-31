<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tdap\DTOs\VistoriaDTO;
use App\Modules\Tdap\Enums\ParecerVistoria;
use App\Modules\Tdap\Models\Caminhao;
use App\Modules\Tdap\Models\Vistoria;
use App\Modules\Tdap\Requests\StoreVistoriaRequest;
use App\Modules\Tdap\Requests\UpdateVistoriaRequest;
use App\Modules\Tdap\Resources\VistoriaIndexResource;
use App\Modules\Tdap\Resources\VistoriaResource;
use App\Modules\Tdap\Services\VistoriaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VistoriaController extends Controller
{
    public function __construct(
        private readonly VistoriaService $service,
    ) {}

    public function index(Request $request): Response
    {
        $perPage = (int) $request->integer('per_page', 15);
        $filtros = $request->only(['parecer', 'placa_id', 'vigente', 'search']);

        $vistorias = $this->service->listar($perPage, $filtros);

        return Inertia::render('Tdap/Vistorias/Index', [
            'vistorias'     => VistoriaIndexResource::collection($vistorias),
            // Mesmos filtros da listagem: o card "Total" conta o que a grade
            // mostra (parecer/vigente ficam de fora — ver o service).
            'estatisticas'  => fn () => $this->service->obterEstatisticas($filtros),
            'caminhoes'     => fn () => Caminhao::ativo()->with('prestador:id,nome')->orderBy('placa')->get(['id', 'placa', 'marca', 'modelo', 'prestador_id']),
            'pareceres'     => ParecerVistoria::options(),
            'filtros'       => $filtros,
            'canCreate'     => $request->user()?->can('tdap.vistorias.create') ?? false,
            'canEdit'       => $request->user()?->can('tdap.vistorias.edit') ?? false,
            'canDelete'     => $request->user()?->can('tdap.vistorias.delete') ?? false,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filtros = $request->only(['parecer', 'placa_id', 'vigente', 'search']);
        $data = $this->service->exportar($filtros);

        $filename = 'vistorias_'.now()->format('Y-m-d_H-i-s').'.csv';

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
        return Inertia::render('Tdap/Vistorias/Create', [
            'caminhoes'        => Caminhao::ativo()->with('prestador:id,nome')->orderBy('placa')->get(['id', 'placa', 'marca', 'modelo', 'cor', 'ano', 'capacidade_m3', 'prestador_id']),
            'pareceres'        => ParecerVistoria::options(),
            'itensEstruturais' => Vistoria::ITENS_ESTRUTURAIS,
            'itensTanque'      => Vistoria::ITENS_TANQUE,
        ]);
    }

    public function store(StoreVistoriaRequest $request): RedirectResponse
    {
        $vistoria = $this->service->criar(VistoriaDTO::fromRequest($request->validated()));

        return redirect()
            ->route('tdap.vistorias.show', $vistoria->id)
            ->with('success', 'Vistoria registrada com sucesso.');
    }

    public function show(Vistoria $vistoria, Request $request): Response
    {
        $vistoria = $this->service->obter($vistoria->id);

        return Inertia::render('Tdap/Vistorias/Show', [
            'vistoria'         => VistoriaResource::make($vistoria),
            'itensEstruturais' => Vistoria::ITENS_ESTRUTURAIS,
            'itensTanque'      => Vistoria::ITENS_TANQUE,
            'canEdit'          => $request->user()?->can('tdap.vistorias.edit') ?? false,
            'canDelete'        => $request->user()?->can('tdap.vistorias.delete') ?? false,
        ]);
    }

    public function edit(Vistoria $vistoria): Response
    {
        return Inertia::render('Tdap/Vistorias/Edit', [
            'vistoria'         => VistoriaResource::make($vistoria->load(['caminhao.prestador'])),
            'caminhoes'        => Caminhao::ativo()->with('prestador:id,nome')->orderBy('placa')->get(['id', 'placa', 'marca', 'modelo', 'cor', 'ano', 'capacidade_m3', 'prestador_id']),
            'pareceres'        => ParecerVistoria::options(),
            'itensEstruturais' => Vistoria::ITENS_ESTRUTURAIS,
            'itensTanque'      => Vistoria::ITENS_TANQUE,
        ]);
    }

    public function update(UpdateVistoriaRequest $request, Vistoria $vistoria): RedirectResponse
    {
        $atualizada = $this->service->atualizar($vistoria->id, VistoriaDTO::fromRequest($request->validated()));

        return redirect()
            ->route('tdap.vistorias.show', $atualizada->id)
            ->with('success', 'Vistoria atualizada.');
    }

    public function destroy(Vistoria $vistoria, Request $request): RedirectResponse
    {
        if (! $request->user()?->can('tdap.vistorias.delete')) {
            abort(403);
        }

        $this->service->deletar($vistoria->id);

        return redirect()
            ->route('tdap.vistorias.index')
            ->with('success', 'Vistoria excluida.');
    }
}
