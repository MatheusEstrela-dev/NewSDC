<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pmda\Models\PmdaPlano;
use App\Modules\Pmda\Services\PlanoPontoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PlanoPontoController extends Controller
{
    public function __construct(private readonly PlanoPontoService $service) {}

    public function store(Request $request, PmdaPlano $plano): RedirectResponse
    {
        $validated = $request->validate([
            'ponto_id' => ['required', 'integer', 'exists:pip_pmda_ponto,id'],
        ]);

        $this->service->vincular($plano, (int) $validated['ponto_id']);

        return back()->with('success', 'Ponto de captação vinculado.');
    }

    public function destroy(PmdaPlano $plano, int $ponto): RedirectResponse
    {
        $this->service->desvincular($plano, $ponto);

        return back()->with('success', 'Ponto de captação desvinculado.');
    }
}
