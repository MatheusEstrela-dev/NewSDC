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
        $plantao->load('snapshots');

        return response()->json([
            'texto' => $this->relatorioService->renderizar($plantao),
        ]);
    }
}
