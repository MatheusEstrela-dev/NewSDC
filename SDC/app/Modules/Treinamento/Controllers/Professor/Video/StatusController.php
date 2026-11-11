<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Professor\Video;

use App\Modules\Treinamento\Models\Video;
use App\Modules\Treinamento\Services\Video\VideoBibliotecaService;
use Illuminate\Http\JsonResponse;

class StatusController extends VideoController
{
    public function __construct(private readonly VideoBibliotecaService $biblioteca) {}

    public function __invoke(Video $video): JsonResponse
    {
        return response()->json($this->biblioteca->situacao($video));
    }
}
