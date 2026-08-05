<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Enums\StatusTreinamento;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\TreinamentoService;
use Illuminate\Http\Request;

class TreinamentoTransicionarStatusController extends Controller
{
    public function __construct(
        private readonly TreinamentoService $treinamentoService
    ) {
    }

    public function __invoke(Request $request, Treinamento $treinamento)
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:EM_ANDAMENTO,CONCLUIDO,CANCELADO'],
        ]);

        try {
            $this->treinamentoService->transicionarStatus(
                $treinamento,
                StatusTreinamento::from($validated['status'])
            );
        } catch (\DomainException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('success', 'Status do treinamento atualizado.');
    }
}
