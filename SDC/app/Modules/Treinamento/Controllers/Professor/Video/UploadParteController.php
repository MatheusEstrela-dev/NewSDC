<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Professor\Video;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Video;
use App\Modules\Treinamento\Models\VideoUpload;
use App\Modules\Treinamento\Requests\Professor\Video\ReceberParteRequest;
use App\Modules\Treinamento\Services\Video\VideoUploadService;
use Illuminate\Http\JsonResponse;

class UploadParteController extends Controller
{
    public function __construct(private readonly VideoUploadService $uploads) {}

    public function __invoke(ReceberParteRequest $request, Video $video, VideoUpload $upload, int $n): JsonResponse
    {
        try {
            $upload = $this->uploads->receberParte($upload, $n, $request->file('parte'));
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'partes_recebidas' => $upload->partes_recebidas,
            'tamanho_recebido' => $upload->tamanho_recebido,
        ]);
    }
}
