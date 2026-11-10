<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Services\ProfessorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfessorCandidatosController extends Controller
{
    public function __construct(
        private readonly ProfessorService $professorService
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $candidatos = $this->professorService->candidatos($request->query('busca'));

        return response()->json($candidatos->map->only(['id', 'name', 'email'])->values());
    }
}
