<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\CicloDoCursoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CursoDesativarController extends Controller
{
    public function __construct(private readonly CicloDoCursoService $ciclo) {}

    public function __invoke(Request $request, Treinamento $treinamento): RedirectResponse
    {
        try {
            $this->ciclo->desativar($treinamento, $request->user());
        } catch (\DomainException $e) {
            return back()->withErrors(['ciclo' => $e->getMessage()]);
        }

        return back()->with('success', 'Curso desativado.');
    }
}
