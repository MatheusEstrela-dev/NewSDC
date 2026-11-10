<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Services\Video\AutorizacaoVideoService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Sub-requisicao do forward_auth do Caddy antes de entregar um arquivo HLS:
 * 204 libera, 403 nega. Sem corpo, sem redirect de login e sem cache da negativa.
 */
class VideoAuthController extends Controller
{
    public function __construct(private readonly AutorizacaoVideoService $autorizacao) {}

    public function __invoke(Request $request): Response
    {
        $uri = (string) ($request->header('X-Forwarded-Uri') ?? $request->header('X-Original-URI') ?? '');

        if ($this->autorizacao->autorizarUri($uri)) {
            return response()->noContent();
        }

        return response('', 403)->header('Cache-Control', 'no-store');
    }
}
