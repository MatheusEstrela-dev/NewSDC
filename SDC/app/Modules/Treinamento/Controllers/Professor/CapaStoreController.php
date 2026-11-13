<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Requests\CapaStoreRequest;
use App\Modules\Treinamento\Services\CapaDoCursoService;
use Illuminate\Http\RedirectResponse;

class CapaStoreController extends Controller
{
    public function __construct(private readonly CapaDoCursoService $capas) {}

    public function __invoke(CapaStoreRequest $request, Treinamento $treinamento): RedirectResponse
    {
        $this->capas->trocar($treinamento, $request->file('capa'));

        return back()->with('success', 'Capa atualizada.');
    }
}
