<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Plantao\Requests\StorePlantonistaRequest;
use App\Modules\Plantao\Services\PlantonistaService;
use Illuminate\Http\RedirectResponse;

class PlantonistaStoreController extends Controller
{
    public function __construct(
        private readonly PlantonistaService $plantonistaService
    ) {
    }

    public function __invoke(StorePlantonistaRequest $request): RedirectResponse
    {
        $dados = $request->validated();

        // Pelo servico, e nao pelo model: a marcacao tem dois pontos de entrada
        // (esta tela e a de governanca de usuarios) e a regra vive num lugar so.
        $plantonista = $this->plantonistaService->marcar(
            User::findOrFail((int) $dados['user_id']),
            $dados['posto'] ?? null,
        );

        return back()->with(
            'success',
            $plantonista->nomeComPosto().' agora pode ser escalado.'
        );
    }
}
