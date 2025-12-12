<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;

/**
 * Trait para respostas API padronizadas
 * Segue DRY (Don't Repeat Yourself)
 * Centraliza formatação de respostas JSON
 */
trait ApiResponseTrait
{
    /**
     * Resposta de sucesso
     */
    protected function successResponse(
        string $message,
        $data = null,
        int $statusCode = 200
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Resposta de erro
     */
    protected function errorResponse(
        string $message,
        $errors = null,
        int $statusCode = 400
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Resposta de validação com erros
     */
    protected function validationErrorResponse(
        $errors,
        string $message = 'Validation errors'
    ): JsonResponse {
        return $this->errorResponse($message, $errors, 422);
    }

    /**
     * Resposta de não autorizado
     */
    protected function unauthorizedResponse(
        string $message = 'Unauthorized'
    ): JsonResponse {
        return $this->errorResponse($message, null, 401);
    }

    /**
     * Resposta de não encontrado
     */
    protected function notFoundResponse(
        string $message = 'Resource not found'
    ): JsonResponse {
        return $this->errorResponse($message, null, 404);
    }

    /**
     * Resposta de criação com sucesso
     */
    protected function createdResponse(
        string $message,
        $data = null
    ): JsonResponse {
        return $this->successResponse($message, $data, 201);
    }

    /**
     * Resposta sem conteúdo (para DELETE)
     */
    protected function noContentResponse(): JsonResponse
    {
        return response()->json(null, 204);
    }
}
