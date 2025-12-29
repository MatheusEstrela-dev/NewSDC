<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compdec\Application\DTOs\OrgaoListDTO;
use App\Modules\Compdec\Application\UseCases\GetHierarquiaOrgaoUseCase;
use App\Modules\Compdec\Domain\Repositories\OrgaoRepositoryInterface;
use Inertia\Inertia;
use Inertia\Response;

class OrgaoShowController extends Controller
{
    public function __construct(
        private readonly OrgaoRepositoryInterface $repository,
        private readonly GetHierarquiaOrgaoUseCase $getHierarquiaUseCase
    ) {
    }

    public function __invoke(int $id): Response
    {
        $orgao = $this->repository->find($id);

        if (!$orgao) {
            abort(404, 'Órgão não encontrado');
        }

        $hierarquia = $this->getHierarquiaUseCase->execute($id);

        return Inertia::render('Compdec/OrgaoShow', [
            'orgao' => OrgaoListDTO::fromEntity($orgao)->toArray(),
            'hierarquia' => $hierarquia,
        ]);
    }
}
