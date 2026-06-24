<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pmda\Models\PmdaComunidade;
use App\Modules\Pmda\Models\PmdaPlano;
use App\Modules\Pmda\Requests\StoreComunidadeRequest;
use App\Modules\Pmda\Services\ComunidadeService;
use Illuminate\Http\RedirectResponse;

class ComunidadeController extends Controller
{
    public function __construct(private readonly ComunidadeService $service) {}

    public function store(StoreComunidadeRequest $request, PmdaPlano $plano): RedirectResponse
    {
        $this->service->adicionar($plano, $request->validated());

        return back()->with('success', 'Comunidade adicionada.');
    }

    public function destroy(PmdaComunidade $comunidade): RedirectResponse
    {
        $this->service->remover($comunidade);

        return back()->with('success', 'Comunidade removida.');
    }
}
