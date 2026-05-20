<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RequestTrace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @OA\Tag(
 *     name="Traces",
 *     description="Consulta de status de requests assincronos (AsynchronousResponse)"
 * )
 */
class TraceController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/traces/{traceId}",
     *     summary="Consulta status de um request assincrono",
     *     description="Retorna o status atual (pending/processing/completed/failed),
     *                  e quando completed inclui URL de download do artefato.",
     *     operationId="getTrace",
     *     tags={"Traces"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="traceId", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="Status retornado"),
     *     @OA\Response(response=404, description="Trace nao encontrado"),
     *     @OA\Response(response=403, description="Trace de outro usuario")
     * )
     */
    public function show(Request $request, string $traceId): JsonResponse
    {
        $trace = RequestTrace::findOrFail($traceId);

        if ($trace->user_id !== null && $trace->user_id !== $request->user()?->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $payload = [
            'trace_id' => $trace->id,
            'type' => $trace->type,
            'status' => $trace->status,
            'started_at' => $trace->started_at?->toIso8601String(),
            'completed_at' => $trace->completed_at?->toIso8601String(),
            'meta' => $trace->meta,
        ];

        if ($trace->isFailed()) {
            $payload['error_message'] = $trace->error_message;
        }

        if ($trace->hasResult()) {
            $payload['download_url'] = route('api.v1.traces.download', ['traceId' => $trace->id]);
        }

        return response()->json($payload);
    }

    /**
     * Download do artefato gerado pelo job (quando aplicavel).
     */
    public function download(Request $request, string $traceId): JsonResponse|StreamedResponse
    {
        $trace = RequestTrace::findOrFail($traceId);

        if ($trace->user_id !== null && $trace->user_id !== $request->user()?->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        if (!$trace->hasResult()) {
            return response()->json(['error' => 'Result not ready'], 409);
        }

        $disk = Storage::disk($trace->result_disk);
        if (!$disk->exists($trace->result_path)) {
            return response()->json(['error' => 'Artifact not found'], 404);
        }

        return $disk->download($trace->result_path);
    }
}
