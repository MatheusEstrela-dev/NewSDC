<?php

declare(strict_types=1);

namespace App\Modules\Rat\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Rat\Http\Requests\UpdateRatRequest;
use App\Modules\Rat\Services\RatWriteService;
use Illuminate\Http\RedirectResponse;

/**
 * Controller de escrita do módulo RAT — atualização e rascunho.
 *
 * SOLID - SRP: só lida com operações de escrita (PUT/PATCH).
 * SOLID - DIP: depende de RatWriteService, não do repositório diretamente.
 */
class RatWriteController extends Controller
{
    public function __construct(
        private readonly RatWriteService $writeService
    ) {}

    /**
     * Atualiza todos os dados do RAT e muda status para Em Andamento.
     */
    public function update(UpdateRatRequest $request, string $id): RedirectResponse
    {
        $this->writeService->update($id, $request->validated());

        return redirect()->route('rat.edit', $id)
            ->with('success', 'RAT atualizado com sucesso!');
    }

    /**
     * Salva rascunho sem alterar o status.
     */
    public function draft(UpdateRatRequest $request, string $id): RedirectResponse
    {
        $this->writeService->saveDraft($id, $request->validated());

        return redirect()->route('rat.edit', $id)
            ->with('success', 'Rascunho salvo com sucesso!');
    }
}
