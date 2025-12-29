<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compdec\Application\UseCases\GetOrgaoStatisticsUseCase;
use App\Modules\Compdec\Application\UseCases\ListOrgaosUseCase;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrgaoIndexController extends Controller
{
    public function __construct(
        private readonly ListOrgaosUseCase $listOrgaosUseCase,
        private readonly GetOrgaoStatisticsUseCase $getStatisticsUseCase
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $filters = $request->only(['tipo', 'status', 'uf', 'municipio_id', 'search', 'orfaos']);

        $orgaos = $this->listOrgaosUseCase->executeAsDTO($filters, 15);
        $statistics = $this->getStatisticsUseCase->executeAsArray();

        return Inertia::render('Compdec/OrgaosIndex', [
            'orgaos' => $orgaos,
            'statistics' => $statistics,
            'filters' => $filters,
            'canManage' => $request->user()?->can('compdec.manage') ?? false,
        ]);
    }
}
