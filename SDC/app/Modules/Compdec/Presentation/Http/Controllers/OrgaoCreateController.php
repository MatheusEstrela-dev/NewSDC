<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compdec\Application\UseCases\CreateOrgaoUseCase;
use App\Modules\Compdec\Domain\ValueObjects\StatusOrgao;
use App\Modules\Compdec\Domain\ValueObjects\TipoOrgao;
use App\Modules\Compdec\Presentation\Http\Requests\CreateOrgaoRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OrgaoCreateController extends Controller
{
    public function __construct(
        private readonly CreateOrgaoUseCase $createOrgaoUseCase
    ) {
    }

    public function create(): Response
    {
        return Inertia::render('Compdec/OrgaoCreate', [
            'tipos' => TipoOrgao::toSelectArray(),
            'statusOptions' => StatusOrgao::toSelectArray(),
        ]);
    }

    public function store(CreateOrgaoRequest $request): RedirectResponse
    {
        $orgao = $this->createOrgaoUseCase->execute(
            $request->validated(),
            $request->user()
        );

        return redirect()
            ->route('compdec.show', $orgao->id)
            ->with('success', 'Órgão criado com sucesso!');
    }
}
