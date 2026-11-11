<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Professor\Video;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Exceptions\OperacaoDeVideoInvalida;
use Closure;
use Illuminate\Http\JsonResponse;

/** Base dos controllers de video: regra violada vira 422 com a mensagem. */
abstract class VideoController extends Controller
{
    protected function responder(Closure $acao): mixed
    {
        try {
            return $acao();
        } catch (OperacaoDeVideoInvalida $e) {
            return new JsonResponse(['message' => $e->getMessage()], 422);
        }
    }
}
