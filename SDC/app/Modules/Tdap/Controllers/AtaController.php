<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tdap\DTOs\AtaDTO;
use App\Modules\Tdap\Models\Ata;
use App\Modules\Tdap\Requests\StoreAtaRequest;
use App\Modules\Tdap\Requests\UpdateAtaRequest;
use App\Modules\Tdap\Resources\AtaIndexResource;
use App\Modules\Tdap\Resources\AtaResource;
use App\Modules\Tdap\Services\AtaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AtaController extends Controller
{
    public function __construct(
        private readonly AtaService $service,
    ) {}

    public function index(Request $request): Response
    {
        $perPage = (int) $request->integer('per_page', 15);
        $filtros = $request->only(['ativo', 'vigente', 'search']);

        $atas = $this->service->listar($perPage, $filtros);

        return Inertia::render('Tdap/Atas/Index', [
            'atas'         => AtaIndexResource::collection($atas),
            'estatisticas' => fn () => $this->service->obterEstatisticas(),
            'filtros'      => $filtros,
            'canCreate'    => $request->user()?->can('tdap.atas.create') ?? false,
            'canEdit'      => $request->user()?->can('tdap.atas.edit') ?? false,
            'canDelete'    => $request->user()?->can('tdap.atas.delete') ?? false,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filtros = $request->only(['ativo', 'vigente', 'search']);
        $data = $this->service->exportar($filtros);

        $filename = 'atas_'.now()->format('Y-m-d_H-i-s').'.csv';

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
        return Inertia::render('Tdap/Atas/Create');
    }

    public function store(StoreAtaRequest $request): RedirectResponse
    {
        $ata = $this->service->criar(AtaDTO::fromRequest($request->validated()));

        return redirect()
            ->route('tdap.atas.show', $ata->id)
            ->with('success', "Ata {$ata->numero} cadastrada.");
    }

    public function show(Ata $ata, Request $request): Response
    {
        $ata = $this->service->obter($ata->id);

        return Inertia::render('Tdap/Atas/Show', [
            'ata'       => AtaResource::make($ata),
            'canEdit'   => $request->user()?->can('tdap.atas.edit') ?? false,
            'canDelete' => $request->user()?->can('tdap.atas.delete') ?? false,
            'canLote'   => $request->user()?->can('tdap.lotes.create') ?? false,
        ]);
    }

    public function edit(Ata $ata): Response
    {
        return Inertia::render('Tdap/Atas/Edit', [
            'ata' => AtaResource::make($ata),
        ]);
    }

    public function update(UpdateAtaRequest $request, Ata $ata): RedirectResponse
    {
        $atualizada = $this->service->atualizar($ata->id, AtaDTO::fromRequest($request->validated()));

        return redirect()
            ->route('tdap.atas.show', $atualizada->id)
            ->with('success', "Ata {$atualizada->numero} atualizada.");
    }

    public function destroy(Ata $ata, Request $request): RedirectResponse
    {
        if (! $request->user()?->can('tdap.atas.delete')) {
            abort(403);
        }

        try {
            $numero = $ata->numero;
            $this->service->deletar($ata->id);

            return redirect()
                ->route('tdap.atas.index')
                ->with('success', "Ata {$numero} excluida.");
        } catch (\DomainException $e) {
            return redirect()
                ->route('tdap.atas.show', $ata->id)
                ->with('error', $e->getMessage());
        }
    }
}
