<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tdap\Services\TdapService;
use App\Modules\Tdap\Enums\TipoMovimentacao;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TdapMovimentacoesController extends Controller
{
    public function __construct(
        private readonly TdapService $tdapService
    ) {
    }

    public function index(Request $request): Response
    {
        $filters = $request->only(['tipo', 'product_id']);
        $movimentacoes = $this->tdapService->listMovimentacoes($filters, 15);

        return Inertia::render('Tdap/Movimentacoes/MovimentacaoIndex', [
            'movimentacoes' => $movimentacoes,
            'filters' => $filters,
            'filterOptions' => [
                'tipos' => TipoMovimentacao::toSelectArray(),
            ],
        ]);
    }

    public function saida(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:tdap_products,id',
            'quantidade' => 'required|integer|min:1',
            'destino' => 'required|string|max:255',
            'motivo' => 'nullable|string|max:255',
            'observacoes' => 'nullable|string',
        ]);

        $this->tdapService->createSaida($validated);

        return redirect()->back()->with('success', 'Saida registrada com sucesso!');
    }

    public function historico(int $product): Response
    {
        $historico = $this->tdapService->getHistoricoMovimentacoes($product);

        return Inertia::render('Tdap/Movimentacoes/MovimentacaoHistorico', [
            'historico' => $historico,
            'product_id' => $product,
        ]);
    }
}
