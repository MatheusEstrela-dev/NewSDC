<?php

declare(strict_types=1);

namespace App\Modules\Rat\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Rat\DTOs\UpdateRatDTO;
use App\Modules\Rat\Http\Requests\UpdateRatRequest;
use App\Modules\Rat\Services\RatWriteService;
use Illuminate\Http\RedirectResponse;

/**
 * Controlador de ação única — Responsabilidade única: finaliza um RAT.
 *
 * Recebe PATCH /rat/{id}/finalize, delega ao RatWriteService e redireciona.
 * Thin controller: Request → DTO → Service.
 */
class RatFinalizeController extends Controller
{
    public function __construct(
        private readonly RatWriteService $writeService,
    ) {}

    public function __invoke(UpdateRatRequest $request, string $id): RedirectResponse
    {
        // Persiste os dados do formulário antes de finalizar
        if ($request->hasAny(['dadosGerais', 'comunicacao', 'local', 'endereco', 'recursos', 'envolvidos', 'vistoria', 'historico'])) {
            $dto = UpdateRatDTO::from($request->validated());
            $this->writeService->saveDraft($id, $dto);
        }

        $rat = $this->writeService->finalize($id);

        return redirect()
            ->route('rat.show', $rat->id)
            ->with('success', 'RAT finalizado com sucesso!');
    }
}
