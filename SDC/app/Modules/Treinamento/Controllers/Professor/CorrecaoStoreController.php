<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Requests\Professor\CorrigirEntregaRequest;
use App\Modules\Treinamento\Services\AcompanhamentoService;
use App\Modules\Treinamento\Services\ProjetoService;
use Illuminate\Http\RedirectResponse;

class CorrecaoStoreController extends Controller
{
    public function __construct(
        private readonly AcompanhamentoService $acompanhamento,
        private readonly ProjetoService $projetos,
    ) {}

    public function __invoke(CorrigirEntregaRequest $request, Treinamento $treinamento, int $entrega): RedirectResponse
    {
        $registro = $this->acompanhamento->entregaDoCurso($treinamento, $entrega);
        $aprovar = $request->boolean('aprovar');

        try {
            $this->projetos->corrigir($registro, $request->user(), $aprovar, $request->input('parecer'));
        } catch (\DomainException $e) {
            return back()->withErrors(['parecer' => $e->getMessage()]);
        }

        return back()->with('success', $aprovar ? 'Entrega aprovada.' : 'Entrega devolvida ao aluno.');
    }
}
