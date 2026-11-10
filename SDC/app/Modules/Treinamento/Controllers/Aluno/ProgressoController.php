<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Aluno;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Requests\RegistrarProgressoRequest;
use App\Modules\Treinamento\Services\ProgressoService;
use App\Modules\Treinamento\Services\TrilhaService;
use App\Modules\Treinamento\Support\InscricaoDoAluno;
use Illuminate\Http\JsonResponse;

class ProgressoController extends Controller
{
    public function __construct(
        private readonly ProgressoService $progresso,
        private readonly TrilhaService $trilha,
    ) {}

    public function __invoke(RegistrarProgressoRequest $request, Treinamento $treinamento, int $item): JsonResponse
    {
        $inscricao = InscricaoDoAluno::aprovada($treinamento, $request->user());
        $moduloItem = InscricaoDoAluno::item($treinamento, $item);

        try {
            $this->progresso->registrarPosicao($inscricao, $moduloItem, $request->integer('posicao'));
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($this->trilha->estado($inscricao)->toArray());
    }
}
