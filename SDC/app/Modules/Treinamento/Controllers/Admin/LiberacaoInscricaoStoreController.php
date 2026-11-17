<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Requests\LiberarInscricaoRequest;
use App\Modules\Treinamento\Services\RegraDeReinscricaoService;
use Illuminate\Http\RedirectResponse;

class LiberacaoInscricaoStoreController extends Controller
{
    public function __construct(
        private readonly RegraDeReinscricaoService $regra
    ) {
    }

    public function __invoke(LiberarInscricaoRequest $request): RedirectResponse
    {
        $dados = $request->validated();

        $this->regra->liberar($dados['cpf'], (int) $dados['curso_id'], $request->user(), $dados['motivo']);

        return back()->with('success', 'CPF liberado para novas inscricoes neste curso.');
    }
}
