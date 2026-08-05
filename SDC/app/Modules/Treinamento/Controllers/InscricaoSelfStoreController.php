<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\InscricaoService;
use Illuminate\Http\Request;

/**
 * Auto-inscricao do servidor interno logado (nao e uma acao de admin - so
 * exige acesso basico ao catalogo, gated pela rota via treinamento.cursos.view).
 */
class InscricaoSelfStoreController extends Controller
{
    public function __construct(
        private readonly InscricaoService $inscricaoService
    ) {
    }

    public function __invoke(Request $request, Treinamento $treinamento)
    {
        try {
            $this->inscricaoService->inscrever($treinamento, $request->user());
        } catch (\DomainException $e) {
            return back()->withErrors(['inscricao' => $e->getMessage()]);
        }

        return back()->with('success', 'Inscricao realizada! Aguarde a aprovacao.');
    }
}
