<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * O Portal de Treinamentos e publico (cidadao externo, sem login de
 * servidor) - a barra do Laravel Debugbar nao deve aparecer aqui mesmo
 * com APP_DEBUG=true, pois expõe queries, sessao e dados de request a
 * quem quer que abra essas paginas.
 */
class DisableDebugbarOnPortal
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->bound('debugbar')) {
            app('debugbar')->disable();
        }

        return $next($request);
    }
}
