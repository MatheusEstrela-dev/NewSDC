<?php

declare(strict_types=1);

namespace App\Modules\Rat\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Rat\Services\RatWriteService;
use Illuminate\Http\RedirectResponse;

/**
 * Single-action controller — SRP: finaliza um RAT.
 *
 * Única responsabilidade: receber PATCH /rat/{id}/finalize,
 * delegar ao RatWriteService e redirecionar.
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
