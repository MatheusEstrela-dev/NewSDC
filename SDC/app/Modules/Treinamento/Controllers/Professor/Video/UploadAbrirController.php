<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Professor\Video;

use App\Modules\Treinamento\Models\Video;
use App\Modules\Treinamento\Requests\Professor\Video\AbrirUploadRequest;
use App\Modules\Treinamento\Services\Video\VideoUploadService;
use Illuminate\Http\JsonResponse;

class UploadAbrirController extends VideoController
{
    public function __construct(private readonly VideoUploadService $uploads) {}

    public function __invoke(AbrirUploadRequest $request, Video $video): JsonResponse
    {
        return $this->responder(function () use ($request, $video) {
            $upload = $this->uploads->abrir(
                $video,
                (int) $request->user()->getKey(),
                $request->validated('nome_original'),
                (int) $request->validated('tamanho_total'),
                $request->validated('mime'),
            );

            return response()->json([
                'upload_id' => $upload->id,
                'tamanho_parte' => $upload->tamanho_parte,
                'total_partes' => $upload->total_partes,
                'expira_em' => $upload->expira_em->toIso8601String(),
            ], 201);
        });
    }
}
