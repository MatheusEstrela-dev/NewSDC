<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Professor\Video;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Requests\Professor\Video\SalvarVideoRequest;
use App\Modules\Treinamento\Services\Video\VideoBibliotecaService;
use Illuminate\Http\JsonResponse;

class VideoStoreController extends Controller
{
    public function __construct(private readonly VideoBibliotecaService $biblioteca) {}

    public function __invoke(SalvarVideoRequest $request): JsonResponse
    {
        $video = $this->biblioteca->criar((int) $request->user()->getKey(), $request->validated('titulo'));

        return response()->json($this->biblioteca->resumo($video), 201);
    }
}
