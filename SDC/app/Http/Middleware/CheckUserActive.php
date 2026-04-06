<?php

namespace App\Http\Middleware;

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
        if (Auth::check() && $request->hasSession()) {
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
                if (!Auth::user()->active) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect()->route('login')->withErrors([
                        'email' => 'Sua conta foi desativada por falta de atualização cadastral (prazo de 6 meses excedido). Entre em contato com o suporte para reativação.',
                    ]);
                }

                $request->session()->put('user_last_active_check', time());
            }
        }

        return $next($request);
    }
}

