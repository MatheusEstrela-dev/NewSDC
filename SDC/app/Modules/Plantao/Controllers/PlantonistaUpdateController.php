<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\Models\Plantonista;
use App\Modules\Plantao\Requests\UpdatePlantonistaRequest;
use App\Modules\Plantao\Services\PlantonistaService;
use Illuminate\Http\RedirectResponse;

/**
 * Ajusta posto e disponibilidade.
 *
 * Inativar NAO mexe nas vagas ja escaladas: a escala publicada e um compromisso
 * assumido, e apagar turno de alguem em silencio e pior do que manter. As vagas
 * futuras precisam ser trocadas a mao, e ai o afetado e avisado.
 */
class PlantonistaUpdateController extends Controller
{
    public function __construct(
        private readonly PlantonistaService $plantonistaService
    ) {
    }

    public function __invoke(UpdatePlantonistaRequest $request, Plantonista $plantonista): RedirectResponse
    {
        $this->plantonistaService->atualizar($plantonista, $request->validated());

        return back()->with('success', 'Cadastro de '.$plantonista->nomeComPosto().' atualizado.');
    }
}
