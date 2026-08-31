<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\Exceptions\MovimentacaoInvalidaException;
use App\Modules\Plantao\Models\Viatura;
use App\Modules\Plantao\Requests\MovimentacaoSaidaRequest;
use App\Modules\Plantao\Services\MovimentacaoViaturaService;
use Illuminate\Http\RedirectResponse;

class MovimentacaoSaidaController extends Controller
{
    public function __construct(
        private readonly MovimentacaoViaturaService $movimentacaoService
    ) {
    }

    public function __invoke(MovimentacaoSaidaRequest $request, Viatura $viatura): RedirectResponse
    {
        try {
            $this->movimentacaoService->registrarSaida($viatura->id, $request->validated());
        } catch (MovimentacaoInvalidaException $e) {
            // A guarda de dominio vira erro de formulario: o usuario ve a razao
            // no campo, nao uma pagina de erro 500.
            return back()->withErrors(['viatura' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'Saida registrada.');
    }
}
