<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tdap\Services\TdapService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TdapProductsController extends Controller
{
    public function __construct(
        private readonly TdapService $tdapService
    ) {
    }

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'ativo']);
        $products = $this->tdapService->listProducts($filters, 15);

        return Inertia::render('Tdap/Products/ProductIndex', [
            'products' => $products,
            'filters' => $filters,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'codigo' => 'required|string|max:50|unique:tdap_products,codigo',
            'descricao' => 'nullable|string',
            'unidade_medida' => 'required|string|max:20',
            'quantidade_minima' => 'nullable|integer|min:0',
        ]);

        $this->tdapService->createProduct($validated);

        return redirect()->back()->with('success', 'Produto cadastrado com sucesso!');
    }

    public function estoque(int $product): Response
    {
        $estoqueData = $this->tdapService->getProductEstoque($product);

        if (empty($estoqueData)) {
            abort(404, 'Produto nao encontrado');
        }

        return Inertia::render('Tdap/Products/ProductEstoque', [
            'estoque' => $estoqueData,
        ]);
    }
}
