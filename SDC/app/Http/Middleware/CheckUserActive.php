<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Auth\SessionVersionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Valida o usuario autenticado a CADA requisicao (sem janela de cache):
 *
 * 1. conta ativa (active + status) -- vale para sessao web e token Sanctum;
 * 2. versao de sessao (users.session_version) igual a gravada no login --
 *    mudanca de cargo, permissao, senha, orgao ou e-mail exige novo login.
 *
 * O usuario ja vem do banco a cada requisicao (guard), entao nao ha query extra.
 */
class CheckUserActive
{
    public const MSG_DESATIVADO = 'Sua conta foi desativada. Entre em contato com o suporte ou com o gestor do sistema.';
    public const MSG_ALTERADO = 'Seus dados de acesso foram alterados; entre novamente.';

    public function __construct(private readonly SessionVersionService $versions)
    {
    }

    /**
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

        $webUser = $request->hasSession() ? Auth::guard('web')->user() : null;
        $viaSession = $webUser instanceof User && $webUser->is($user);

        if (!$user->canAuthenticate()) {
            return $this->rejeitar($request, self::MSG_DESATIVADO, $viaSession);
        }

        if ($viaSession && !$this->versions->matches($request->session(), $user)) {
            return $this->rejeitar($request, self::MSG_ALTERADO, true);
        }

        return $next($request);
    }

    private function rejeitar(Request $request, string $mensagem, bool $viaSession): Response
    {
        if ($viaSession) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if (!$viaSession || ($request->expectsJson() && !$request->header('X-Inertia'))) {
            return response()->json(['message' => $mensagem], $viaSession ? 401 : 403);
        }

        return redirect()->route('login')->setStatusCode(303)->withErrors(['cpf' => $mensagem]);
    }
}
