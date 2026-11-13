<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\CapaDoCursoService;
use Illuminate\Http\RedirectResponse;

class CapaDestroyController extends Controller
{
    public function __construct(private readonly CapaDoCursoService $capas) {}

    public function __invoke(Treinamento $treinamento): RedirectResponse
    {
        $this->capas->remover($treinamento);

        return back()->with('success', 'Capa removida.');
    }
}
