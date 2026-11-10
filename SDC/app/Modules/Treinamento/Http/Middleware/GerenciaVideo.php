<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Http\Middleware;

use App\Modules\Treinamento\Models\Video;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gerenciar um video da biblioteca: o dono ou quem edita cursos.
 */
class GerenciaVideo
{
    public function handle(Request $request, Closure $next): Response
    {
        $video = $request->route('video');

        // Sem binding aplicado ainda, chega o id cru.
        if (! $video instanceof Video && $video !== null) {
            $video = Video::query()->find($video);
        }

        abort_unless($video instanceof Video, 404);

        $user = $request->user();

        abort_unless(
            $user !== null
                && ((int) $video->user_id === (int) $user->getKey() || $user->can('treinamento.cursos.edit')),
            403
        );

        return $next($request);
    }
}
