<?php

declare(strict_types=1);

namespace App\Modules\Rat\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Rat\Application\Services\RatService;
use App\Modules\Rat\Http\Requests\ListRatRequest;
use App\Modules\Rat\Http\Resources\RatResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controller de extração de dados do módulo RAT.
 *
 * Responsabilidade única: endpoints de dados (JSON para impressão e export CSV).
 * Separado do RatController para manter cada controller com no máximo 5 métodos púблicos.
 */
class RatDataController extends Controller
{
    public function __construct(
        private readonly RatService $service
    ) {}

    /**
     * Retorna os dados completos de um RAT em JSON — usado pelo modal de impressão.
     */
    public function showJson(string $id): JsonResponse
    {
        $rat = $this->service->findById($id);
        abort_if(is_null($rat), 404, 'RAT não encontrado.');

        // resolve() serializa sem o wrapper {data: ...} do ResourceResponse
        return response()->json((new RatResource($rat))->resolve());
    }

    /**
     * Gera e retorna download CSV com os RATs filtrados.
     */
    public function export(ListRatRequest $request): StreamedResponse
    {
        return $this->service->export($request->toFilterDTO());
    }
}
