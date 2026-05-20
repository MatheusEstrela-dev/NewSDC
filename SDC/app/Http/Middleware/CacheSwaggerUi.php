<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cache de resposta para rotas estaticas do Swagger UI (l5-swagger).
 *
 * Aplica Cache-Control: public, max-age=<configurable> em respostas
 * bem-sucedidas. Bypass via query string ?regenerate=1 para uso
 * por tier internal/admin durante atualizacao de documentacao.
 */
class CacheSwaggerUi
{
    public function handle(Request $request, Closure $next, int $maxAgeSeconds = 3600): Response
    {
        if ($request->boolean('regenerate')) {
            return $next($request);
        }

        $response = $next($request);

        if ($response->isSuccessful()) {
            $response->headers->set('Cache-Control', "public, max-age={$maxAgeSeconds}");
        }

        return $response;
    }
}
