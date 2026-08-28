<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\Models\Plantonista;
use Illuminate\Http\RedirectResponse;

/**
 * Remove da lista de escalaveis.
 *
 * Nao apaga historico: as vagas ja escaladas guardam plantonista_id (FK para
 * users) e plantonista_nome espelhado, entao continuam legiveis depois que o
 * cadastro sai daqui.
 */
class PlantonistaDestroyController extends Controller
{
    public function __invoke(Plantonista $plantonista): RedirectResponse
    {
        $nome = $plantonista->nomeComPosto();

        $plantonista->delete();

        return back()->with('success', $nome.' removido da lista de plantonistas.');
    }
}
