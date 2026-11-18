<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Gestao;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\Gestao\GestaoCursoConsulta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Itens do cronograma de um mes (ou intervalo), para o calendario (axios,
 * nao Inertia - o componente de calendario busca de novo a cada mudanca de
 * mes, como o EscalaCalendario do Plantao ja faz).
 */
class CronogramaIndexController extends Controller
{
    public function __construct(private readonly GestaoCursoConsulta $consulta)
    {
    }

    public function __invoke(Request $request, Treinamento $treinamento): JsonResponse
    {
        $de = $request->query('de') ?: now()->startOfMonth()->toDateString();
        $ate = $request->query('ate') ?: now()->endOfMonth()->toDateString();

        return response()->json(
            $this->consulta->cronograma($treinamento, $de, $ate)
        );
    }
}
