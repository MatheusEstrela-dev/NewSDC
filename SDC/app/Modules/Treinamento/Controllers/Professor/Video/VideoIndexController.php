<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Professor\Video;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Services\Video\VideoBibliotecaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VideoIndexController extends Controller
{
    public function __construct(private readonly VideoBibliotecaService $biblioteca) {}

    public function __invoke(Request $request): JsonResponse
    {
        return response()->json($this->biblioteca->listar(
            $request->query('q') !== null ? (string) $request->query('q') : null,
            $request->boolean('prontos'),
        ));
    }
}
