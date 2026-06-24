<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pmda\Models\PmdaComunidade;
use App\Modules\Pmda\Models\PmdaRepresentante;
use App\Modules\Pmda\Requests\StoreRepresentanteRequest;
use App\Modules\Pmda\Requests\UpdateRepresentanteRequest;
use App\Modules\Pmda\Services\RepresentanteService;
use Illuminate\Http\RedirectResponse;

class RepresentanteController extends Controller
{
    public function __construct(private readonly RepresentanteService $service) {}

    public function store(StoreRepresentanteRequest $request, PmdaComunidade $comunidade): RedirectResponse
    {
        $this->service->adicionar($comunidade, $request->validated());

        return back()->with('success', 'Representante adicionado.');
    }

    public function update(UpdateRepresentanteRequest $request, PmdaRepresentante $representante): RedirectResponse
    {
        $this->service->atualizar($representante, $request->validated());

        return back()->with('success', 'Representante atualizado.');
    }

    public function destroy(PmdaRepresentante $representante): RedirectResponse
    {
        $this->service->remover($representante);

        return back()->with('success', 'Representante removido.');
    }
}
