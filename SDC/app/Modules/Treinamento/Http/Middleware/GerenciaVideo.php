<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Http\Middleware;

use App\Modules\Treinamento\Models\Video;
use App\Modules\Treinamento\Support\PermissaoDeVideo;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gerenciar um video da biblioteca: o dono ou quem edita cursos.
 */
class GerenciaVideo
{
    public function __construct(private readonly PermissaoDeVideo $permissao) {}

    public function handle(Request $request, Closure $next): Response
    {
        $video = $request->route('video');

        // Sem binding aplicado ainda, chega o id cru.
        if (! $video instanceof Video && $video !== null) {
            $video = Video::query()->find($video);
        }

        abort_unless($video instanceof Video, 404);

        abort_unless($this->permissao->podeGerenciar($video, $request->user()), 403);

        return $next($request);
    }
}
