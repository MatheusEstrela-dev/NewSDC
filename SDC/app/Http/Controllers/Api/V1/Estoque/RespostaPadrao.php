<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Estoque;

use Illuminate\Http\JsonResponse;

/** Envelopes SuccessResponse/ErrorResponse do Swagger (SwaggerController). */
trait RespostaPadrao
{
    private function sucesso(string $mensagem, mixed $dados, int $status = 200): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $mensagem, 'data' => $dados], $status);
    }

    /** @param array<string, mixed> $erros */
    private function erro(string $mensagem, int $status, array $erros = []): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $mensagem, 'errors' => (object) $erros], $status);
    }
}
