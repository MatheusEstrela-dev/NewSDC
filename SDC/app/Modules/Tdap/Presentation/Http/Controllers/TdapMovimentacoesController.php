<?php

namespace App\Modules\Tdap\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tdap\Application\UseCases\ListMovimentacoesUseCase;
use App\Modules\Tdap\Application\UseCases\GetProductHistoricoUseCase;
use App\Modules\Tdap\Application\UseCases\CreateSaidaEstoqueUseCase;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TdapMovimentacoesController extends Controller
{
    public function __construct(
        private readonly ListMovimentacoesUseCase $listMovimentacoesUseCase,
        private readonly GetProductHistoricoUseCase $getProductHistoricoUseCase,
        private readonly CreateSaidaEstoqueUseCase $createSaidaEstoqueUseCase,
    ) {}

    /**
     * Lista de movimentações
     */
    public function index(Request $request): Response|\Illuminate\Http\RedirectResponse
    {
        try {
            $filters = $request->only(['tipo', 'product_id', 'data_inicio', 'data_fim', 'search', 'sort_field', 'sort_direction']);
            $perPage = $request->input('per_page', 15);

            $result = $this->listMovimentacoesUseCase->executeAsDTO($filters, $perPage);

            return Inertia::render('Tdap/MovimentacoesIndex', [
                'movimentacoes' => $result['data'],
                'pagination' => $result['pagination'],
                'filters' => $result['filters'],
                'statistics' => $result['statistics'],
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erro ao carregar movimentações. Por favor, tente novamente.');
        }
    }

    /**
     * Cria saída de estoque
     */
    public function saida(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|integer|exists:tdap_products,id',
                'quantidade' => 'required|integer|min:1',
                'data_movimentacao' => 'nullable|date',
                'origem' => 'nullable|string',
                'destino' => 'nullable|string',
                'solicitante_id' => 'nullable|integer|exists:users,id',
                'responsavel_id' => 'required|integer|exists:users,id',
                'documento_referencia' => 'nullable|string',
                'observacoes' => 'nullable|string',
            ]);

            $result = $this->createSaidaEstoqueUseCase->execute($validated);

            return redirect()->route('tdap.movimentacoes.index')
                            ->with('success', "Saída de estoque processada com sucesso! {$result['lotes_utilizados']} lote(s) utilizado(s).");
        } catch (\DomainException $e) {
            return redirect()->back()
                            ->withInput()
                            ->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()->back()
                            ->withInput()
                            ->with('error', 'Erro ao processar saída de estoque. Por favor, tente novamente.');
        }
    }

    /**
     * Histórico de movimentações de um produto
     */
    public function historico(int $productId): Response
    {
        try {
            $result = $this->getProductHistoricoUseCase->execute($productId);

            return Inertia::render('Tdap/ProductHistorico', $result->toArray());
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erro ao carregar histórico do produto. Por favor, tente novamente.');
        }
    }
}
