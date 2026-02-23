<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Decretacoes\Application\DTOs\ProcessoInputDTO;
use App\Modules\Decretacoes\Application\Services\ProcessoService;
use App\Modules\Decretacoes\Presentation\Http\Requests\ProcessoStoreRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Controller: Criação de Processo (POST)
 * Seguindo papiro.md - ORQUESTRAÇÃO
 *
 * Fluxo: Request -> InputDTO -> Service -> OutputDTO -> Redirect
 *
 * O OutputDTO é usado internamente, mas a resposta é redirect
 * pois Inertia intercepta e faz navegação SPA automaticamente.
 * Flash messages são passadas via session (padrão Inertia).
 */
class ProcessoStoreController extends Controller
{
    public function __construct(
        private readonly ProcessoService $service
    ) {
    }

    public function __invoke(ProcessoStoreRequest $request): RedirectResponse
    {
        // 1. Request validado -> InputDTO
        $inputDTO = ProcessoInputDTO::fromArray($request->validated());

        // 2. InputDTO -> Service -> OutputDTO
        $outputDTO = $this->service->cadastrar($inputDTO);

        // 3. OutputDTO usado para decisão de fluxo
        if ($outputDTO->status === 'success') {
            // Inertia intercepta redirect e navega via SPA
            // Flash data fica disponível via usePage().props.flash
            return redirect()
                ->route('decretacoes.show', ['id' => $outputDTO->id])
                ->with('flash', [
                    'type' => 'success',
                    'message' => $outputDTO->mensagem,
                    'data' => $outputDTO->toArray(),  // DTO como prop!
                ]);
        }

        return redirect()
            ->back()
            ->withInput()
            ->with('flash', [
                'type' => 'error',
                'message' => $outputDTO->mensagem,
            ]);
    }
}
