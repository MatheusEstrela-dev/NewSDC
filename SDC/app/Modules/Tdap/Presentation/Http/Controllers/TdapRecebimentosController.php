<?php

namespace App\Modules\Tdap\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tdap\Application\UseCases\ListRecebimentosUseCase;
use App\Modules\Tdap\Application\UseCases\ShowRecebimentoUseCase;
use App\Modules\Tdap\Application\UseCases\CreateRecebimentoUseCase;
use App\Modules\Tdap\Application\UseCases\ProcessarRecebimentoUseCase;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TdapRecebimentosController extends Controller
{
    public function __construct(
        private readonly ListRecebimentosUseCase $listRecebimentosUseCase,
        private readonly ShowRecebimentoUseCase $showRecebimentoUseCase,
        private readonly CreateRecebimentoUseCase $createRecebimentoUseCase,
        private readonly ProcessarRecebimentoUseCase $processarRecebimentoUseCase,
    ) {}

    /**
     * Lista de recebimentos
     */
    public function index(Request $request): Response|\Illuminate\Http\RedirectResponse
    {
        try {
            $filters = $request->only(['status', 'nota_fiscal', 'data_inicio', 'data_fim', 'search', 'sort_field', 'sort_direction']);
            $perPage = $request->input('per_page', 15);

            $result = $this->listRecebimentosUseCase->executeAsDTO($filters, $perPage);

            return Inertia::render('Tdap/RecebimentosIndex', [
                'recebimentos' => $result['data'],
                'pagination' => $result['pagination'],
                'filters' => $result['filters'],
                'statistics' => $result['statistics'],
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erro ao carregar recebimentos. Por favor, tente novamente.');
        }
    }

    /**
     * Cria novo recebimento (Modal TDPA)
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'ordem_compra_id' => 'nullable|integer',
                'nota_fiscal' => 'required|string|max:100',
                'placa_veiculo' => 'required|string|max:20',
                'transportadora' => 'nullable|string|max:255',
                'motorista_nome' => 'required|string|max:255',
                'motorista_documento' => 'nullable|string|max:20',
                'doca_descarga' => 'nullable|string|max:20',
                'data_chegada' => 'nullable|date',
                'observacoes' => 'nullable|string',
                'itens' => 'required|array|min:1',
                'itens.*.product_id' => 'required|integer|exists:tdap_products,id',
                'itens.*.quantidade_nota' => 'required|integer|min:1',
                'itens.*.quantidade_conferida' => 'required|integer|min:0',
                'itens.*.numero_lote' => 'nullable|string|max:100',
                'itens.*.data_fabricacao' => 'nullable|date',
                'itens.*.data_validade' => 'nullable|date',
                'itens.*.tem_avaria' => 'boolean',
                'itens.*.tipo_avaria' => 'nullable|string',
                'itens.*.quantidade_avariada' => 'nullable|integer|min:0',
                'itens.*.observacoes' => 'nullable|string',
            ]);

            $recebimento = $this->createRecebimentoUseCase->execute($validated);

            return redirect()->route('tdap.recebimentos.show', $recebimento->id)
                            ->with('success', "Recebimento {$recebimento->numero_recebimento} criado com sucesso!");
        } catch (\DomainException $e) {
            return redirect()->back()
                            ->withInput()
                            ->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()->back()
                            ->withInput()
                            ->with('error', 'Erro ao criar recebimento. Por favor, tente novamente.');
        }
    }

    /**
     * Visualiza detalhes do recebimento
     */
    public function show(int $id): Response
    {
        try {
            $recebimento = $this->showRecebimentoUseCase->execute($id);

            return Inertia::render('Tdap/RecebimentoShow', [
                'recebimento' => $recebimento->load(['itens.product', 'conferidoPor', 'aprovadoPor']),
            ]);
        } catch (\DomainException $e) {
            abort(404, $e->getMessage());
        } catch (\Exception $e) {
            abort(500, 'Erro ao carregar detalhes do recebimento.');
        }
    }

    /**
     * Processa recebimento (aprova e cria lotes/movimentações)
     */
    public function processar(int $id, Request $request)
    {
        try {
            $userId = $request->user()->id;
            $recebimento = $this->processarRecebimentoUseCase->execute($id, $userId);

            return redirect()->route('tdap.recebimentos.show', $recebimento->id)
                            ->with('success', "Recebimento {$recebimento->numero_recebimento} processado com sucesso!");
        } catch (\DomainException $e) {
            return redirect()->back()
                            ->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()->back()
                            ->with('error', 'Erro ao processar recebimento. Por favor, tente novamente.');
        }
    }
}
