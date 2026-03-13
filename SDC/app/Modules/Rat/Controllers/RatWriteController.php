<?php

declare(strict_types=1);

namespace App\Modules\Rat\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Rat\DTOs\CreateRatDTO;
use App\Modules\Rat\DTOs\UpdateRatDTO;
use App\Modules\Rat\Http\Requests\CreateRatRequest;
use App\Modules\Rat\Http\Requests\UpdateRatRequest;
use App\Modules\Rat\Services\RatWriteService;
use Illuminate\Http\RedirectResponse;

/**
 * Controller de escrita do módulo RAT — criação, atualização e rascunho.
 *
 * Thin controller: recebe Request → cria DTO → delega ao Service.
 */
class RatWriteController extends Controller
{
    public function __construct(
        private readonly RatWriteService $writeService
    ) {}

    /**
     * Cria um novo RAT (em branco ou com dados iniciais) e redireciona para edição.
     */
    public function store(CreateRatRequest $request): RedirectResponse
    {
        $dto = CreateRatDTO::from($request->validated());
        $rat = $this->writeService->createWithData($dto);

        return redirect()->route('rat.edit', $rat->id)
            ->with('success', 'RAT criado com sucesso!');
    }

    /**
     * Atualiza todos os dados do RAT e muda status para Em Andamento.
     */
    public function update(UpdateRatRequest $request, string $id): RedirectResponse
    {
        $dto = UpdateRatDTO::from($request->validated());
        $this->writeService->update($id, $dto);

        return redirect()->route('rat.edit', $id)
            ->with('success', 'RAT atualizado com sucesso!');
    }

    /**
     * Salva rascunho sem alterar o status.
     */
    public function draft(UpdateRatRequest $request, string $id): RedirectResponse
    {
        $dto = UpdateRatDTO::from($request->validated());
        $this->writeService->saveDraft($id, $dto);

        return redirect()->route('rat.edit', $id)
            ->with('success', 'Rascunho salvo com sucesso!');
    }
}
