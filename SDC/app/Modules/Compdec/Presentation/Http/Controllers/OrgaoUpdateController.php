<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compdec\Application\DTOs\OrgaoListDTO;
use App\Modules\Compdec\Application\UseCases\UpdateOrgaoUseCase;
use App\Modules\Compdec\Domain\Repositories\OrgaoRepositoryInterface;
use App\Modules\Compdec\Domain\ValueObjects\StatusOrgao;
use App\Modules\Compdec\Domain\ValueObjects\TipoOrgao;
use App\Modules\Compdec\Presentation\Http\Requests\UpdateOrgaoRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OrgaoUpdateController extends Controller
{
    public function __construct(
        private readonly OrgaoRepositoryInterface $repository,
        private readonly UpdateOrgaoUseCase $updateOrgaoUseCase
    ) {
    }

    public function edit(int $id): Response
    {
        $orgao = $this->repository->find($id);

        if (!$orgao) {
            abort(404, 'Órgão não encontrado');
        }

        return Inertia::render('Compdec/OrgaoEdit', [
            'orgao' => OrgaoListDTO::fromEntity($orgao)->toArray(),
            'tipos' => TipoOrgao::toSelectArray(),
            'statusOptions' => StatusOrgao::toSelectArray(),
        ]);
    }

    public function update(int $id, UpdateOrgaoRequest $request): RedirectResponse
    {
        $orgao = $this->updateOrgaoUseCase->execute(
            $id,
            $request->validated(),
            $request->user()
        );

        return redirect()
            ->route('compdec.show', $orgao->id)
            ->with('success', 'Órgão atualizado com sucesso!');
    }
}
