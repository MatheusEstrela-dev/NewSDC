<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AjudaHumanitaria\Services\BeneficiarioService;
use App\Modules\AjudaHumanitaria\Enums\StatusBeneficiario;
use App\Modules\AjudaHumanitaria\Enums\SituacaoVulnerabilidade;
use App\Modules\AjudaHumanitaria\Enums\TipoCadastroBeneficiario;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BeneficiarioController extends Controller
{
    public function __construct(
        private readonly BeneficiarioService $beneficiarioService
    ) {
    }

    public function index(Request $request): Response
    {
        $filters = $request->only([
            'search',
            'status',
            'situacao_vulnerabilidade',
            'tipo_cadastro',
            'municipio_id',
            'abrigo_id',
        ]);

        $beneficiarios = $this->beneficiarioService->list($filters, 15);
        $statistics = $this->beneficiarioService->getStatistics();

        return Inertia::render('AjudaHumanitaria/Beneficiarios/BeneficiarioIndex', [
            'beneficiarios' => $beneficiarios,
            'statistics' => $statistics,
            'filters' => $filters,
            'filterOptions' => [
                'status' => StatusBeneficiario::toSelectArray(),
                'situacoes' => SituacaoVulnerabilidade::toSelectArray(),
                'tipos_cadastro' => TipoCadastroBeneficiario::toSelectArray(),
            ],
        ]);
    }

    public function show(int $id): Response
    {
        $beneficiario = $this->beneficiarioService->findById($id);

        if (!$beneficiario) {
            abort(404, 'Beneficiario nao encontrado');
        }

        return Inertia::render('AjudaHumanitaria/Beneficiarios/BeneficiarioShow', [
            'beneficiario' => $beneficiario,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome_responsavel' => 'required|string|max:255',
            'cpf' => 'required|string|size:11|unique:beneficiarios,cpf',
            'situacao_vulnerabilidade' => 'required|string',
            'tipo_cadastro' => 'required|string',
        ]);

        $beneficiario = $this->beneficiarioService->create($validated);

        return redirect()->route('ajuda-humanitaria.beneficiarios.show', $beneficiario->id)
            ->with('success', 'Beneficiario cadastrado com sucesso!');
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'nome_responsavel' => 'sometimes|string|max:255',
            'situacao_vulnerabilidade' => 'sometimes|string',
            'status' => 'sometimes|string',
        ]);

        $this->beneficiarioService->update($id, $validated);

        return redirect()->back()->with('success', 'Beneficiario atualizado com sucesso!');
    }

    public function destroy(int $id)
    {
        $this->beneficiarioService->delete($id);

        return redirect()->route('ajuda-humanitaria.beneficiarios.index')
            ->with('success', 'Beneficiario removido com sucesso!');
    }
}
