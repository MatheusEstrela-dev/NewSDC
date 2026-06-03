<?php

namespace App\Http\Controllers\Api\V1\Rat;

use App\Http\Controllers\Controller;
use App\Modules\Rat\Services\RatHistoricoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HistoricoController extends Controller
{
    public function __construct(
        private readonly RatHistoricoService $historicoService,
    ) {}

    public function index(Request $request, string $id): JsonResponse
    {
        $timeline = $this->historicoService->timeline(
            (int) $id,
            $request->integer('per_page', 20)
        );

        return response()->json([
            'data' => $timeline['data'],
            'meta' => [
                'total'        => $timeline['total'],
                'current_page' => $timeline['page'],
            ],
        ]);
    }

    public function recent(Request $request, string $id): JsonResponse
    {
        $items = $this->historicoService->recent(
            (int) $id,
            $request->integer('limite', 10)
        );

        return response()->json(['data' => $items]);
    }
}
