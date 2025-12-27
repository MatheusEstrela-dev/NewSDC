<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Decretacoes\Domain\Repositories\ProcessoRepositoryInterface;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller: Processo Show
 */
class ProcessoShowController extends Controller
{
    public function __construct(
        private readonly ProcessoRepositoryInterface $processoRepository
    ) {
    }

    public function __invoke(int $id): Response
    {
        $processo = $this->processoRepository->findById($id);

        if (!$processo) {
            abort(404, 'Processo não encontrado');
        }

        return Inertia::render('Decretacoes/ProcessoShow', [
            'processo' => $processo->load([
                'municipios',
                'danosHumanos.municipio',
                'danosMateriais.municipio',
                'prejuizos.municipio',
                'anexos',
                'logs.user'
            ]),
        ]);
    }
}
