<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\Exceptions\PassagemInvalidaException;
use App\Modules\Plantao\Models\Plantao;
use App\Modules\Plantao\Requests\EncerrarPassagemRequest;
use App\Modules\Plantao\Services\PassagemServicoService;
use Illuminate\Http\RedirectResponse;

class PassagemEncerrarController extends Controller
{
    public function __construct(
        private readonly PassagemServicoService $passagemService
    ) {
    }

    public function __invoke(EncerrarPassagemRequest $request, Plantao $plantao): RedirectResponse
    {
        $dados = $request->validated();

        try {
            $this->passagemService->encerrar(
                $plantao->id,
                $dados['snapshots'],
                $dados['ocorrencias_destaque'] ?? null,
                (int) $request->user()->id
            );
        } catch (PassagemInvalidaException $e) {
            return back()->withErrors(['plantao' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'Plantao encerrado. Aguardando aceite de quem assume.');
    }
}
