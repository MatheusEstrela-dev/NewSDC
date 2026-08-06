<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Checa pelo model, nao pelo guard: este middleware tambem roda no
        // grupo "api" sob auth:sanctum (guard != "web") e precisa continuar
        // validando ali. Guards que autenticam outros models (ex: "cidadao")
        // nao tem a coluna "active" e devem ser ignorados aqui.
        if (!$user instanceof User) {
            return $next($request);
        }

        if (!$request->hasSession()) {
            if (!$user->active) {
                return response()->json([
                    'message' => 'Seu usuario esta desativado. Entre em contato com o suporte ou com o gestor do sistema.',
                ], 403);
            }

            return $next($request);
        }

        if (Auth::check()) {
            $shouldCheck = true;
            $lastCheck = $request->session()->get('user_last_active_check');

            if ($lastCheck instanceof \Carbon\Carbon) {
                $shouldCheck = now()->diffInMinutes($lastCheck) > 5;
            } elseif (is_int($lastCheck)) {
                $shouldCheck = (time() - $lastCheck) > 300;
            } else {
                $request->session()->forget('user_last_active_check');
            }

            if ($shouldCheck) {
                if (!$user->active) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect()->route('login')->withErrors([
                        'cpf' => 'Seu usuario esta desativado. Entre em contato com o suporte ou com o gestor do sistema.',
                    ]);
                }

                $request->session()->put('user_last_active_check', time());
            }
        }

        return $next($request);
    }
}

