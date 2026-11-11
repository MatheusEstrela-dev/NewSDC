<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Professor\Video;

use App\Modules\Treinamento\Models\Video;
use App\Modules\Treinamento\Models\VideoUpload;
use App\Modules\Treinamento\Services\Video\VideoUploadService;
use Illuminate\Http\JsonResponse;

class UploadFinalizarController extends VideoController
{
    public function __construct(private readonly VideoUploadService $uploads) {}

    public function __invoke(Video $video, VideoUpload $upload): JsonResponse
    {
        return $this->responder(fn () => response()->json([
            'status' => $this->uploads->finalizar($upload)->status->value,
        ]));
    }
}
