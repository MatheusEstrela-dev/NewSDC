<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Decretacoes\Services\ProcessoService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProcessoController extends Controller
{
    public function __construct(
        private readonly ProcessoService $processoService
    ) {
    }

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'tipo_decreto']);
        $processos = $this->processoService->list($filters, 15);
        $statistics = $this->processoService->getStatistics();

        return Inertia::render('Decretacoes/ProcessoIndex', [
            'processos' => $processos,
            'statistics' => $statistics,
            'filters' => $filters,
        ]);
    }

    public function show(int $id): Response
    {
        $processo = $this->processoService->findById($id);

        if (!$processo) {
            abort(404, 'Processo nao encontrado');
        }

        return Inertia::render('Decretacoes/ProcessoShow', [
            'processo' => $processo,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'numero_processo' => 'required|string|max:100',
            'municipio' => 'required|string|max:255',
            'tipo_decreto' => 'required|string',
        ]);

        $processo = $this->processoService->create($validated);

        return redirect()->route('decretacoes.processos.show', $processo->id)
            ->with('success', 'Processo cadastrado com sucesso!');
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'status' => 'sometimes|string',
            'observacoes' => 'sometimes|string',
        ]);

        $this->processoService->update($id, $validated);

        return redirect()->back()->with('success', 'Processo atualizado com sucesso!');
    }

    public function destroy(int $id)
    {
        $this->processoService->delete($id);

        return redirect()->route('decretacoes.processos.index')
            ->with('success', 'Processo removido com sucesso!');
    }
}
