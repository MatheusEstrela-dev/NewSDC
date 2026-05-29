<?php

namespace App\Http\Middleware;

use App\Models\EmailChangeRequest;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloqueia operacoes de escrita sensiveis enquanto o usuario tem
 * um pedido de troca de e-mail ativo (pending).
 *
 * Leitura (GETs) e a propria UI de validacao continuam liberadas
 * para nao impedir produtividade — o popup persistente do frontend
 * ja garante pressao visual.
 */
class RequireEmailVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $hasPending = EmailChangeRequest::activeFor($user->id)
            ->where('expires_at', '>', now())
            ->exists();

        if (!$hasPending) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message'  => 'Voce precisa validar o novo e-mail antes de continuar.',
                'redirect' => null,
            ], 423);
        }

        return back()->with(
            'error',
            'Valide o novo e-mail (verifique sua caixa de entrada) antes de continuar.'
        );
    }
}
