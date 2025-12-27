<?php

namespace App\Modules\Tdap\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tdap\Application\UseCases\ListProductsUseCase;
use App\Modules\Tdap\Application\UseCases\CreateProductUseCase;
use App\Modules\Tdap\Application\UseCases\GetEstoqueUseCase;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TdapProductsController extends Controller
{
    public function __construct(
        private readonly ListProductsUseCase $listProductsUseCase,
        private readonly CreateProductUseCase $createProductUseCase,
        private readonly GetEstoqueUseCase $getEstoqueUseCase,
    ) {}

    /**
     * Lista de produtos do depósito
     */
    public function index(Request $request): Response|\Illuminate\Http\RedirectResponse
    {
        try {
            $filters = $request->only(['tipo', 'grupo_risco', 'eh_composto', 'search', 'sort_field', 'sort_direction']);
            $perPage = $request->input('per_page', 15);

            $result = $this->listProductsUseCase->executeAsDTO($filters, $perPage);

            return Inertia::render('Tdap/ProductsIndex', [
                'products' => $result['data'],
                'pagination' => $result['pagination'],
                'filters' => $result['filters'],
                'statistics' => $result['statistics'],
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erro ao carregar produtos. Por favor, tente novamente.');
        }
    }

    /**
     * Cria novo produto
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'codigo' => 'required|string|max:50|unique:tdap_products,codigo',
                'nome' => 'required|string|max:255',
                'descricao' => 'nullable|string',
                'tipo' => 'required|string|in:cesta_basica,kit_limpeza,colchao,outros',
                'eh_composto' => 'boolean',
                'volume_unitario_m3' => 'numeric|min:0',
                'peso_unitario_kg' => 'numeric|min:0',
                'estoque_minimo' => 'integer|min:0',
                'estoque_maximo' => 'nullable|integer|min:0',
                'estrategia_armazenamento' => 'nullable|string|in:fifo,fefo,lifo',
                'grupo_risco' => 'required|string|in:ALIMENTO,QUIMICO,GERAL',
                'dias_alerta_validade' => 'integer|min:1',
                'observacoes' => 'nullable|string',
            ]);

            $product = $this->createProductUseCase->execute($validated);

            return redirect()->route('tdap.products.index')
                            ->with('success', "Produto {$product->nome} criado com sucesso!");
        } catch (\DomainException $e) {
            return redirect()->back()
                            ->withInput()
                            ->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()->back()
                            ->withInput()
                            ->with('error', 'Erro ao criar produto. Por favor, tente novamente.');
        }
    }

    /**
     * Visualiza estoque de um produto
     */
    public function estoque(int $productId): Response
    {
        try {
            $estoque = $this->getEstoqueUseCase->execute($productId);

            return Inertia::render('Tdap/ProductEstoque', [
                'estoque' => $estoque->toArray(),
            ]);
        } catch (\DomainException $e) {
            abort(404, $e->getMessage());
        } catch (\Exception $e) {
            abort(500, 'Erro ao carregar estoque do produto.');
        }
    }
}
