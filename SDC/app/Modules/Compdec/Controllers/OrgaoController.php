<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compdec\Services\OrgaoService;
use App\Modules\Compdec\Enums\StatusOrgao;
use App\Modules\Compdec\Enums\TipoOrgao;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrgaoController extends Controller
{
    public function __construct(
        private readonly OrgaoService $orgaoService
    ) {
    }

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'tipo']);
        $orgaos = $this->orgaoService->list($filters, 15);
        $statistics = $this->orgaoService->getStatistics();

        return Inertia::render('Compdec/OrgaosIndex', [
            'orgaos' => $orgaos,
            'statistics' => $statistics,
            'filters' => $filters,
            'filterOptions' => [
                'status' => StatusOrgao::toSelectArray(),
                'tipos' => TipoOrgao::toSelectArray(),
            ],
        ]);
    }

    public function show(int $id): Response
    {
        $orgao = $this->orgaoService->findById($id);

        if (!$orgao) {
            abort(404, 'Orgao nao encontrado');
        }

        return Inertia::render('Compdec/OrgaoShow', [
            'orgao' => $orgao,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'codigo' => 'required|string|max:50',
            'tipo' => 'required|string',
        ]);

        $orgao = $this->orgaoService->create($validated);

        return redirect()->route('compdec.orgaos.show', $orgao->id)
            ->with('success', 'Orgao cadastrado com sucesso!');
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'nome' => 'sometimes|string|max:255',
            'status' => 'sometimes|string',
        ]);

        $this->orgaoService->update($id, $validated);

        return redirect()->back()->with('success', 'Orgao atualizado com sucesso!');
    }

    public function destroy(int $id)
    {
        $this->orgaoService->delete($id);

        return redirect()->route('compdec.orgaos.index')
            ->with('success', 'Orgao removido com sucesso!');
    }
}
