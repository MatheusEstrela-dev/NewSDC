<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Compartilha o cidadao autenticado como prop Inertia (auth.cidadao), sem
 * tocar em App\Http\Middleware\HandleInertiaRequests (que so conhece o guard
 * "web"). Aplicado apenas as rotas do Portal de Treinamentos.
 */
class ShareCidadaoInertiaData
{
    public function handle(Request $request, Closure $next): Response
    {
        $cidadao = Auth::guard('cidadao')->user();

        Inertia::share('auth.cidadao', $cidadao ? [
            'id' => $cidadao->id,
            'name' => $cidadao->name,
            'email' => $cidadao->email,
        ] : null);

        return $next($request);
    }
}
