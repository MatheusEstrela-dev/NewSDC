<?php

declare(strict_types=1);

namespace App\Modules\Rat\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Rat\Services\RatWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Controlador de ação única — finaliza uma ocorrência RAT.
 *
 * FLUXO: PATCH compdec/rat/{id}/finalizar -> RatWriteService.finalize() -> Redirect
 */
class RatFinalizeController extends Controller
{
    public function __construct(
        private readonly RatWriteService $writeService,
    ) {}

    public function __invoke(Request $request, int $id): RedirectResponse
    {
        $ocorrencia = $this->writeService->finalize($id);

        return redirect()
            ->route('compdec.rat.show', $ocorrencia->id)
            ->with('success', 'Ocorrência finalizada com sucesso!');
    }
}