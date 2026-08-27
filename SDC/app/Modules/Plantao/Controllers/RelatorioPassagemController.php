<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\Models\Plantao;
use App\Modules\Plantao\Services\RelatorioPassagemService;
use Illuminate\Http\JsonResponse;

class RelatorioPassagemController extends Controller
{
    public function __construct(
        private readonly RelatorioPassagemService $relatorioService
    ) {
    }

    public function __invoke(Plantao $plantao): JsonResponse
    {
        // A flag exclusiva_sobreaviso vem da viatura (spec 3.3.1), nao do texto
        // de anotacao: o eager load evita uma query por snapshot no relatorio.
        $plantao->load(['snapshots.viatura:id,exclusiva_sobreaviso']);

        return response()->json([
            'texto' => $this->relatorioService->renderizar($plantao),
        ]);
    }
}
