<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Autentica e, em seguida, valida a conta (ativa + versao de sessao).
     *
     * Embutir o CheckUserActive aqui garante que TODA rota `auth:*` (inclusive
     * `auth:sanctum` fora de grupo com o middleware listado) rejeite usuario
     * desativado, sem depender de lembrar de listar o middleware por grupo.
     */
    public function handle($request, Closure $next, ...$guards)
    {
        return parent::handle(
            $request,
            fn ($req) => app(CheckUserActive::class)->handle($req, $next),
            ...$guards
        );
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        \Illuminate\Support\Facades\Log::warning('User unauthenticated on path: ' . $request->path() . ' expectsJson: ' . ($request->expectsJson() ? 'true' : 'false'));
        return $request->expectsJson() ? null : route('login');
    }
}
