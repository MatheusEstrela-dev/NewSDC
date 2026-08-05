<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Requests\RegistrarPresencaLoteRequest;
use App\Modules\Treinamento\Services\PresencaService;
use Illuminate\Http\JsonResponse;

/**
 * RF07 - recebe o lote de check-ins acumulados offline e sincroniza. Cada
 * item e processado individualmente (falha em um nao aborta o lote); o
 * frontend usa a resposta para saber quais itens remover da fila local.
 */
class PresencaSincronizarController extends Controller
{
    public function __construct(
        private readonly PresencaService $presencaService
    ) {
    }

    public function __invoke(RegistrarPresencaLoteRequest $request): JsonResponse
    {
        $resultados = $this->presencaService->sincronizarLote(
            $request->validated('itens'),
            $request->user()
        );

        return response()->json(['resultados' => $resultados]);
    }
}
