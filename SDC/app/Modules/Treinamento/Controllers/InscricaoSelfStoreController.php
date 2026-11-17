<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Models\Turma;
use App\Modules\Treinamento\Services\InscricaoService;
use App\Modules\Treinamento\Requests\InscreverRequest;

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

    public function __invoke(InscreverRequest $request, Treinamento $treinamento)
    {
        // Turma e buscada so pelo id (sem escopo no treinamento): e o proprio
        // InscricaoService::turmaDaInscricao() quem recusa turma de outro
        // curso, com a mensagem de dominio correta.
        $turmaId = $request->turmaId();
        $turma = $turmaId !== null ? Turma::find($turmaId) : null;

        try {
            $inscricao = $this->inscricaoService->inscrever($treinamento, $request->user(), $request->sexo(), $turma);
        } catch (\DomainException $e) {
            return back()->withErrors(['inscricao' => $e->getMessage()]);
        }

        return back()->with('success', $this->inscricaoService->mensagemDeSucesso($inscricao));
    }
}
