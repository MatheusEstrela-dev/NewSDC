<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Professor\Video;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Video;
use App\Modules\Treinamento\Models\VideoUpload;
use App\Modules\Treinamento\Services\Video\VideoUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class UploadCancelarController extends Controller
{
    public function __construct(private readonly VideoUploadService $uploads) {}

    public function __invoke(Video $video, VideoUpload $upload): JsonResponse|Response
    {
        try {
            $this->uploads->cancelar($upload);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->noContent();
    }
}
