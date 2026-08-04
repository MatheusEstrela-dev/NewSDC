<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Jobs\RecordUserLogin;
use App\Models\AuditLog;
use App\Models\User;
use App\Modules\Treinamento\Services\CidadaoAuthService;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
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
     *
     * Tela de login unica para servidor (guard "web") e cidadao do Portal de
     * Treinamentos (guard "cidadao"): o CPF decide o caminho. So desviamos
     * para o cidadao quando o CPF NAO pertence a nenhum servidor - assim a
     * logica de seguranca do login interno (bloqueio progressivo, hash
     * constante, auditoria) continua 100% intacta para quem e servidor.
     */
    public function store(LoginRequest $request, CidadaoAuthService $cidadaoAuth): RedirectResponse
    {
        $cpf = preg_replace('/\D/', '', (string) $request->input('cpf'));

        if (!User::where('cpf', $cpf)->exists()) {
            return $this->storeCidadao($request, $cidadaoAuth, $cpf);
        }

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
     * Ramo do login unificado para CPFs que nao pertencem a nenhum servidor -
     * tenta autenticar no guard "cidadao" (Portal de Treinamentos). Usa o
     * mesmo campo de erro ('cpf') que o login interno, entao a tela mostra a
     * mensagem no mesmo lugar independente do caminho seguido.
     */
    private function storeCidadao(LoginRequest $request, CidadaoAuthService $cidadaoAuth, string $cpf): RedirectResponse
    {
        if ($cidadaoAuth->tooManyAttempts($cpf, $request->ip())) {
            $seconds = $cidadaoAuth->availableIn($cpf, $request->ip());
            throw ValidationException::withMessages([
                'cpf' => "Muitas tentativas. Tente novamente em {$seconds} segundos.",
            ]);
        }

        $autenticado = $cidadaoAuth->attempt(
            $cpf,
            (string) $request->input('password'),
            $request->boolean('remember'),
            $request->ip()
        );

        if (!$autenticado) {
            throw ValidationException::withMessages([
                'cpf' => 'CPF ou senha invalidos.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('portal.treinamento.catalogo'));
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
