<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Professor\Video;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\ModuloItem;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Models\Video;
use App\Modules\Treinamento\Requests\Professor\Video\VincularVideoRequest;
use App\Modules\Treinamento\Services\ConteudoCursoService;
use App\Modules\Treinamento\Services\Video\VideoBibliotecaService;
use Illuminate\Http\JsonResponse;

class ItemVideoVincularController extends Controller
{
    public function __construct(
        private readonly ConteudoCursoService $conteudo,
        private readonly VideoBibliotecaService $biblioteca,
    ) {}

    public function __invoke(VincularVideoRequest $request, Treinamento $treinamento, ModuloItem $item): JsonResponse
    {
        $this->conteudo->garantirDoCurso($treinamento, $item);

        try {
            $video = Video::withTrashed()->find($request->validated('video_id'));

            if ($video === null) {
                throw new \DomainException('Video nao encontrado na biblioteca.');
            }

            $item = $this->biblioteca->vincular($item, $video);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['id' => $item->id, 'video_id' => $item->video_id]);
    }
}
