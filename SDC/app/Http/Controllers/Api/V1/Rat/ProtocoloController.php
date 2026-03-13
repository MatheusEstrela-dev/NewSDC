<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Rat;

use App\Http\Controllers\Controller;
use App\Modules\Rat\Http\Resources\RatResource;
use App\Modules\Rat\Models\Rat;
use App\Modules\Rat\Services\RatService;
use App\Modules\Rat\Services\RatWriteService;
use App\Modules\Rat\DTOs\RatFilterDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * API REST para protocolos RAT (v1).
 * Delega ao módulo Rat sem duplicar lógica.
 */
class ProtocoloController extends Controller
{
    public function __construct(
        private readonly RatService      $ratService,
        private readonly RatWriteService $writeService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = RatFilterDTO::fromArray($request->only(['status', 'protocolo', 'per_page']));
        $rats    = $this->ratService->list($filters);

        return RatResource::collection($rats);
    }

    public function show(string $id): RatResource|JsonResponse
    {
        $rat = $this->ratService->findById($id);
        abort_if(is_null($rat), 404, 'RAT não encontrado');

        return new RatResource($rat);
    }

    public function store(Request $request): JsonResponse
    {
        $rat = $this->writeService->create();

        return response()->json(new RatResource($rat), 201);
    }

    public function update(Request $request, string $id): RatResource
    {
        abort(405, 'Use a rota /rat/{id} (PUT) para atualizar um RAT.');
    }

    public function destroy(string $id): JsonResponse
    {
        $this->ratService->delete($id);

        return response()->json(['message' => 'RAT removido.']);
    }
}
