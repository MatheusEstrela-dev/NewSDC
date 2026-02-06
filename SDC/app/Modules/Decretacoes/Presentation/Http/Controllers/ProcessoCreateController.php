<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Decretacoes\Application\UseCases\GetProcessoFormDataUseCase;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller: Formulário de Criação de Processo
 *
 * Regra de Ouro: Controller não monta dados.
 * UseCase é o porteiro final - entrega ViewModel pronto.
 */
class ProcessoCreateController extends Controller
{
    /**
     * Eficiência: UseCase injetado no método, DTO direto no Inertia
     */
    public function __invoke(GetProcessoFormDataUseCase $useCase): Response
    {
        return Inertia::render('Decretacoes/ProcessoCreate', $useCase->execute()->toArray());
    }
}
