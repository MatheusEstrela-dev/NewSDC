<?php

declare(strict_types=1);

namespace App\Modules\Rat\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Rat\Services\RatWriteService;
use Illuminate\Http\RedirectResponse;

/**
 * Controlador de ação única — Responsabilidade única: finaliza um RAT.
 *
 * Recebe PATCH /rat/{id}/finalize, delega ao RatWriteService e redireciona.
 */
class RatFinalizeController extends Controller
{
    public function __construct(
        private readonly RatWriteService $writeService,
    ) {}

    public function __invoke(string $id): RedirectResponse
    {
        $rat = $this->writeService->finalize($id);

        return redirect()
            ->route('rat.show', $rat->id)
            ->with('success', 'RAT finalizado com sucesso!');
    }
}
