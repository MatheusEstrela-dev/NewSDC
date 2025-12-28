<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Presentation\Http\Controllers\Beneficiarios;

use App\Http\Controllers\Controller;
use App\Modules\AjudaHumanitaria\Domain\Repositories\BeneficiarioRepositoryInterface;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller: Visualizar Beneficiário
 *
 * Exibe detalhes completos de um beneficiário
 */
class BeneficiarioShowController extends Controller
{
    public function __construct(
        private readonly BeneficiarioRepositoryInterface $beneficiarioRepository
    ) {
    }

    public function __invoke(int $id): Response
    {
        $beneficiario = $this->beneficiarioRepository->findOrFail($id);

        return Inertia::render('AjudaHumanitaria/Beneficiarios/BeneficiarioShow', [
            'beneficiario' => $beneficiario->load([
                'membros',
                'abrigo',
                'auxilios',
                'abrigos_historico',
                'municipio',
            ]),
        ]);
    }
}
