<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\Exceptions\PassagemInvalidaException;
use App\Modules\Plantao\Models\Plantao;
use App\Modules\Plantao\Requests\AceitarPassagemRequest;
use App\Modules\Plantao\Services\PassagemServicoService;
use Illuminate\Http\RedirectResponse;

class PassagemAceitarController extends Controller
{
    public function __construct(
        private readonly PassagemServicoService $passagemService
    ) {
    }

    public function __invoke(AceitarPassagemRequest $request, Plantao $plantao): RedirectResponse
    {
        $dados = $request->validated();
        $userId = (int) $request->user()->id;

        try {
            if ($dados['acao'] === 'divergencia') {
                $this->passagemService->apontarDivergencia(
                    $plantao->id,
                    $userId,
                    $dados['divergencia']
                );

                return back()->with('success', 'Divergencia registrada.');
            }

            $this->passagemService->aceitar($plantao->id, $userId);
        } catch (PassagemInvalidaException $e) {
            return back()->withErrors(['plantao' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'Passagem de servico aceita.');
    }
}
