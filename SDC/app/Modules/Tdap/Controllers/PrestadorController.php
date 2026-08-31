<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tdap\DTOs\PrestadorDTO;
use App\Modules\Tdap\Models\Prestador;
use App\Modules\Tdap\Requests\StorePrestadorRequest;
use App\Modules\Tdap\Requests\UpdatePrestadorRequest;
use App\Modules\Tdap\Resources\PrestadorIndexResource;
use App\Modules\Tdap\Resources\PrestadorResource;
use App\Modules\Tdap\Services\PrestadorService;
use App\Support\UnidadeFederativa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrestadorController extends Controller
{
    public function __construct(
        private readonly PrestadorService $service,
    ) {}

    public function index(Request $request): Response
    {
        $perPage = (int) $request->integer('per_page', 15);
        $filtros = $request->only(['ativo', 'uf', 'search']);

        $prestadores = $this->service->listar($perPage, $filtros);

        return Inertia::render('Tdap/Prestadores/Index', [
            'prestadores'  => PrestadorIndexResource::collection($prestadores),
            // Mesmos filtros da grade: o card "Total" conta o que a tabela
            // mostra (`ativo` fica de fora — ver o service).
            'estatisticas' => fn () => $this->service->obterEstatisticas($filtros),
            'filtros'      => $filtros,
            'ufs'          => UnidadeFederativa::options(),
            'canCreate'    => $request->user()?->can('tdap.prestadores.create') ?? false,
            'canEdit'      => $request->user()?->can('tdap.prestadores.edit') ?? false,
            'canDelete'    => $request->user()?->can('tdap.prestadores.delete') ?? false,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filtros = $request->only(['ativo', 'uf', 'search']);
        $data = $this->service->exportar($filtros);

        $filename = 'prestadores_'.now()->format('Y-m-d_H-i-s').'.csv';

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
        return Inertia::render('Tdap/Prestadores/Create', [
            'ufs' => UnidadeFederativa::options(),
        ]);
    }

    public function store(StorePrestadorRequest $request): RedirectResponse
    {
        $prestador = $this->service->criar(
            PrestadorDTO::fromRequest($request->validated()),
        );

        return redirect()
            ->route('tdap.prestadores.show', $prestador->id)
            ->with('success', "Prestador {$prestador->nome} cadastrado. Cadastre os caminhoes-tanque para habilita-lo em cronogramas.");
    }

    public function show(Prestador $prestador, Request $request): Response
    {
        $prestador = $this->service->obter($prestador->id);

        return Inertia::render('Tdap/Prestadores/Show', [
            'prestador'  => PrestadorResource::make($prestador),
            'canEdit'    => $request->user()?->can('tdap.prestadores.edit') ?? false,
            'canDelete'  => $request->user()?->can('tdap.prestadores.delete') ?? false,
        ]);
    }

    public function edit(Prestador $prestador): Response
    {
        return Inertia::render('Tdap/Prestadores/Edit', [
            'prestador' => PrestadorResource::make($prestador),
            'ufs'       => UnidadeFederativa::options(),
        ]);
    }

    public function update(UpdatePrestadorRequest $request, Prestador $prestador): RedirectResponse
    {
        $atualizado = $this->service->atualizar(
            $prestador->id,
            PrestadorDTO::fromRequest($request->validated()),
        );

        return redirect()
            ->route('tdap.prestadores.show', $atualizado->id)
            ->with('success', "Prestador {$atualizado->nome} atualizado.");
    }

    public function destroy(Prestador $prestador, Request $request): RedirectResponse
    {
        if (! $request->user()?->can('tdap.prestadores.delete')) {
            abort(403);
        }

        try {
            $nome = $prestador->nome;
            $this->service->deletar($prestador->id);

            return redirect()
                ->route('tdap.prestadores.index')
                ->with('success', "Prestador {$nome} excluido.");
        } catch (\DomainException $e) {
            return redirect()
                ->route('tdap.prestadores.show', $prestador->id)
                ->with('error', $e->getMessage());
        }
    }
}
