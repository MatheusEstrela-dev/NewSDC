<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Jobs\RecordUserLogin;
use App\Models\AuditLog;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();
        // Prefere o IP detectado pelo frontend (WebRTC + ipify via header
        // X-Client-IP, resolvido pelo middleware ResolveClientIp). Em ambiente
        // Docker o $request->ip() seria so o gateway (172.x.x.x); o do client
        // e mais informativo para auditoria.
        $clientIp = $request->attributes->get('client_ip') ?? $request->ip();

        // Pos-processamento (recordLogin + AuditLog + invalidacao de cache) fora
        // do caminho critico da resposta. Ver Jobs\RecordUserLogin.
        RecordUserLogin::dispatch($user->id, $clientIp, $request->userAgent());

        // Onboarding: usuario com senha provisoria precisa troca-la antes de
        // qualquer outra navegacao. Quebra o redirect->intended para nao cair
        // numa rota qualquer salva na session.
        if ($user->must_change_password) {
            return redirect()->route('password.first-access');
        }

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $userId = Auth::id();

        Auth::guard('web')->logout();

        AuditLog::logLogout($userId);

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
