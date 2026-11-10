<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Professor\Video;

use App\Modules\Treinamento\Models\ModuloItem;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Models\Video;
use App\Modules\Treinamento\Requests\Professor\Video\VincularVideoRequest;
use App\Modules\Treinamento\Services\ConteudoCursoService;
use App\Modules\Treinamento\Services\Video\VideoBibliotecaService;
use Illuminate\Http\JsonResponse;

class ItemVideoVincularController extends VideoController
{
    public function __construct(
        private readonly ConteudoCursoService $conteudo,
        private readonly VideoBibliotecaService $biblioteca,
    ) {}

    public function __invoke(VincularVideoRequest $request, Treinamento $treinamento, ModuloItem $item): JsonResponse
    {
        $this->conteudo->garantirDoCurso($treinamento, $item);

        return $this->responder(function () use ($request, $item) {
            $item = $this->biblioteca->vincular($item, Video::query()->findOrFail($request->validated('video_id')));

            return response()->json(['id' => $item->id, 'video_id' => $item->video_id]);
        });
    }
}
