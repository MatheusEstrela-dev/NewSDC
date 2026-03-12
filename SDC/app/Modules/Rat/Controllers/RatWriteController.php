<?php

declare(strict_types=1);

namespace App\Modules\Rat\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Rat\DTO\RatBoDTO;
use App\Modules\Rat\Requests\UpdateRatRequest;
use App\Modules\Rat\Services\RatWriteService;
use Illuminate\Http\RedirectResponse;

/**
 * Controller de escrita complementar do módulo RAT.
 * Lida com salvar como rascunho explicitamente (PATCH).
 *
 * FLUXO: PATCH compdec/rat/{id}/draft -> RatBoDTO -> RatWriteService.saveDraft() -> Redirect
 */
class RatWriteController extends Controller
{
    public function __construct(
        private readonly RatWriteService $writeService,
    ) {}

    /**
     * Salva rascunho mantendo status = 0.
     */
    public function draft(UpdateRatRequest $request, int $id): RedirectResponse
    {
        $dto        = RatBoDTO::fromArray($request->validated());
        $ocorrencia = $this->writeService->saveDraft($id, $dto);

        return redirect()
            ->route('compdec.rat.edit', $ocorrencia->id)
            ->with('success', 'Rascunho salvo com sucesso!');
    }
}