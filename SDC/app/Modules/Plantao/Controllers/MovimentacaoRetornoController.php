<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\Exceptions\MovimentacaoInvalidaException;
use App\Modules\Plantao\Models\ViaturaMovimentacao;
use App\Modules\Plantao\Requests\MovimentacaoRetornoRequest;
use App\Modules\Plantao\Services\MovimentacaoViaturaService;
use Illuminate\Http\RedirectResponse;

class MovimentacaoRetornoController extends Controller
{
    public function __construct(
        private readonly MovimentacaoViaturaService $movimentacaoService
    ) {
    }

    public function __invoke(
        MovimentacaoRetornoRequest $request,
        ViaturaMovimentacao $movimentacao
    ): RedirectResponse {
        try {
            $this->movimentacaoService->registrarRetorno($movimentacao->id, $request->validated());
        } catch (MovimentacaoInvalidaException $e) {
            return back()->withErrors(['movimentacao' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'Retorno registrado.');
    }
}
