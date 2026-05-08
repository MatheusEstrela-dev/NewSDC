<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compdec\DTOs\EquipeDTO;
use App\Modules\Compdec\Models\CompdecEquipe;
use App\Modules\Compdec\Models\Orgao;
use App\Modules\Compdec\Requests\StoreEquipeRequest;
use App\Modules\Compdec\Requests\UpdateEquipeRequest;
use App\Modules\Compdec\Resources\EquipeIndexResource;
use App\Modules\Compdec\Resources\EquipeResource;
use App\Modules\Compdec\Services\EquipeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class EquipeController extends Controller
{
    public function __construct(
        private readonly EquipeService $service,
    ) {}

    public function index(Orgao $orgao): JsonResponse
    {
        $this->authorize('viewAny', CompdecEquipe::class);

        $equipes = $this->service->listarPorOrgao($orgao->id);

        return EquipeIndexResource::collection($equipes)->response();
    }

    public function show(Orgao $orgao, CompdecEquipe $equipe): JsonResponse
    {
        $equipe = $this->service->obter($orgao->id, $equipe->id);
        $this->authorize('view', $equipe);

        return response()->json([
            'data' => EquipeResource::make($equipe),
        ]);
    }

    public function store(StoreEquipeRequest $request, Orgao $orgao): RedirectResponse
    {
        $equipe = $this->service->criar(
            $orgao->id,
            EquipeDTO::fromRequest($request->validated()),
        );

        return back()->with('success', "Membro {$equipe->nome} adicionado a equipe.");
    }

    public function update(UpdateEquipeRequest $request, Orgao $orgao, CompdecEquipe $equipe): RedirectResponse
    {
        $this->service->atualizar(
            $orgao->id,
            $equipe->id,
            EquipeDTO::fromRequest($request->validated()),
        );

        return back()->with('success', 'Membro atualizado.');
    }

    public function destroy(Orgao $orgao, CompdecEquipe $equipe): RedirectResponse
    {
        $this->authorize('delete', $equipe);
        $this->service->deletar($orgao->id, $equipe->id);

        return back()->with('success', 'Membro removido da equipe.');
    }

    public function restore(Orgao $orgao, int $equipe): RedirectResponse
    {
        $this->authorize('update', CompdecEquipe::class);
        $this->service->restaurar($orgao->id, $equipe);

        return back()->with('success', 'Membro restaurado.');
    }
}
