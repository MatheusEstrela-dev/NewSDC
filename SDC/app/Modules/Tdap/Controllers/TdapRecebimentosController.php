<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tdap\Services\TdapService;
use App\Modules\Tdap\Enums\StatusRecebimento;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TdapRecebimentosController extends Controller
{
    public function __construct(
        private readonly TdapService $tdapService
    ) {
    }

    public function index(Request $request): Response
    {
        $filters = $request->only(['status', 'product_id']);
        $recebimentos = $this->tdapService->listRecebimentos($filters, 15);

        return Inertia::render('Tdap/Recebimentos/RecebimentoIndex', [
            'recebimentos' => $recebimentos,
            'filters' => $filters,
            'filterOptions' => [
                'status' => StatusRecebimento::toSelectArray(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:tdap_products,id',
            'quantidade' => 'required|integer|min:1',
            'data_recebimento' => 'required|date',
            'origem' => 'required|string|max:255',
            'numero_nota' => 'nullable|string|max:100',
            'observacoes' => 'nullable|string',
        ]);

        $validated['status'] = 'pendente';
        $validated['user_id'] = auth()->id();

        $this->tdapService->createRecebimento($validated);

        return redirect()->back()->with('success', 'Recebimento registrado com sucesso!');
    }

    public function show(int $recebimento): Response
    {
        $rec = $this->tdapService->findRecebimentoById($recebimento);

        if (!$rec) {
            abort(404, 'Recebimento nao encontrado');
        }

        return Inertia::render('Tdap/Recebimentos/RecebimentoShow', [
            'recebimento' => $rec,
        ]);
    }

    public function processar(int $recebimento)
    {
        $result = $this->tdapService->processarRecebimento($recebimento);

        if (!$result) {
            return redirect()->back()->with('error', 'Nao foi possivel processar o recebimento.');
        }

        return redirect()->back()->with('success', 'Recebimento processado com sucesso!');
    }
}
