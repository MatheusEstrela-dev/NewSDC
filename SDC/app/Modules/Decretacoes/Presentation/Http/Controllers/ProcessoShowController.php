<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Decretacoes\Application\UseCases\GetProcessoShowUseCase;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller: Processo Show
 *
 * Regra de Ouro: O Controller não "monta" dados.
 * Apenas pede ao UseCase e entrega o DTO para o Inertia::render()
 */
class ProcessoShowController extends Controller
{
    /**
     * Eficiência máxima: UseCase já entrega o DTO tipado e higienizado
     */
    public function __invoke(int $id, GetProcessoShowUseCase $useCase): Response
    {
        $viewModel = $useCase->execute($id);

        if (!$viewModel) {
            throw new NotFoundHttpException('Processo não encontrado');
        }

        // Inertia converte automaticamente via Arrayable
        return Inertia::render('Decretacoes/ProcessoShow', [
            'processo' => $viewModel,
        ]);
    }
}
