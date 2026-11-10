<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Professor\Video;

use App\Modules\Treinamento\Models\Video;
use App\Modules\Treinamento\Models\VideoUpload;
use App\Modules\Treinamento\Requests\Professor\Video\ReceberParteRequest;
use App\Modules\Treinamento\Services\Video\VideoUploadService;
use Illuminate\Http\JsonResponse;

class UploadParteController extends VideoController
{
    public function __construct(private readonly VideoUploadService $uploads) {}

    public function __invoke(ReceberParteRequest $request, Video $video, VideoUpload $upload, int $n): JsonResponse
    {
        return $this->responder(function () use ($request, $upload, $n) {
            $upload = $this->uploads->receberParte($upload, $n, $request->file('parte'));

            return response()->json([
                'partes_recebidas' => $upload->partes_recebidas,
                'tamanho_recebido' => $upload->tamanho_recebido,
            ]);
        });
    }
}
