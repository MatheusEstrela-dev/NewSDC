<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloqueia qualquer rota autenticada ate que o usuario troque a senha provisoria
 * (must_change_password = true). Redireciona para /first-access mantendo o
 * proprio fluxo de logout aberto para o usuario poder sair sem trocar.
 *
 * Whitelist: rotas que precisam ficar acessiveis enquanto o usuario nao trocou
 * a senha (a propria tela de primeiro acesso e o logout).
 */
class EnsurePasswordChanged
{
    /**
     * @param  \Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->must_change_password) {
            return $next($request);
        }

        $routeName = optional($request->route())->getName();

        if (in_array($routeName, ['password.first-access', 'password.first-access.update', 'logout'], true)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Voce precisa trocar a senha provisoria antes de continuar.',
                'redirect' => route('password.first-access'),
            ], 423);
        }

        return redirect()->route('password.first-access');
    }
}
