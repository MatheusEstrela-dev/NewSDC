<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Professor\Video;

use App\Modules\Treinamento\Models\Video;
use App\Modules\Treinamento\Models\VideoUpload;
use App\Modules\Treinamento\Services\Video\VideoUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class UploadCancelarController extends VideoController
{
    public function __construct(private readonly VideoUploadService $uploads) {}

    public function __invoke(Video $video, VideoUpload $upload): JsonResponse|Response
    {
        return $this->responder(function () use ($upload) {
            $this->uploads->cancelar($upload);

            return response()->noContent();
        });
    }
}
