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
        if (Auth::check()) {
            // Check session cache first to avoid DB query every time
            if (!$request->session()->has('user_last_active_check') || 
                now()->diffInMinutes($request->session()->get('user_last_active_check')) > 5) {
                
                if (!Auth::user()->active) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect()->route('login')->withErrors([
                        'email' => 'Sua conta foi desativada por falta de atualização cadastral (prazo de 6 meses excedido). Entre em contato com o suporte para reativação.',
                    ]);
                }
                
                // Update check time
                $request->session()->put('user_last_active_check', now());
            }
        }

        return $next($request);
    }
}

