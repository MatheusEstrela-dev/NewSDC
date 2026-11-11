<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Professor\Video;

use App\Modules\Treinamento\Models\Video;
use App\Modules\Treinamento\Services\Video\VideoBibliotecaService;

class VideoDestroyController extends VideoController
{
    public function __construct(private readonly VideoBibliotecaService $biblioteca) {}

    public function __invoke(Video $video): mixed
    {
        return $this->responder(function () use ($video) {
            $this->biblioteca->excluir($video);

            return response()->noContent();
        });
    }
}
