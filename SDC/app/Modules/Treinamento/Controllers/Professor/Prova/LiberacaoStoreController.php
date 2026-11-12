<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Professor\Prova;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Prova;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Requests\Professor\Prova\LiberarTentativaRequest;
use App\Modules\Treinamento\Services\LiberacaoProvaService;
use App\Modules\Treinamento\Services\ProvaConteudoService;
use Illuminate\Http\JsonResponse;

class LiberacaoStoreController extends Controller
{
    public function __construct(
        private readonly ProvaConteudoService $conteudo,
        private readonly LiberacaoProvaService $liberacoes,
    ) {}

    public function __invoke(LiberarTentativaRequest $request, Treinamento $treinamento, Prova $prova): JsonResponse
    {
        $this->conteudo->garantirDoCurso($treinamento, $prova);

        $liberacao = $this->liberacoes->liberar(
            $treinamento,
            $prova,
            (int) $request->validated('inscricao_id'),
            (int) $request->validated('quantidade'),
            $request->user(),
        );

        return response()->json([
            'id' => $liberacao->id,
            'prova_id' => $prova->id,
            'inscricao_id' => $liberacao->inscricao_id,
            'quantidade' => $liberacao->quantidade,
            'liberado_em' => $liberacao->created_at?->toIso8601String(),
        ], 201);
    }
}
