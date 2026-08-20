<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\TransientToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Faz a ability do token de API valer, e nao so a permissao do usuario dono.
 *
 * Sem isto, a ability gravada em personal_access_tokens e decorativa: o
 * middleware `can:` consulta a permissao do usuario (Spatie), nunca o escopo do
 * token. Um token emitido para ler Ajuda Humanitaria alcancava qualquer rota de
 * /api/v1 permitida ao dono, inclusive POST.
 *
 * As abilities exigidas saem dos proprios `can:` da rota, e nao de uma segunda
 * lista paralela: uma rota nova nasce protegida sem ninguem precisar lembrar de
 * declarar o escopo duas vezes.
 *
 * Compatibilidade: token com ability curinga ('*') passa em tudo, porque
 * Sanctum trata '*' como coringa em tokenCan(). Tokens antigos seguem
 * funcionando; o aperto vale para os emitidos com escopo.
 *
 * Sessao web autentica com TransientToken, que nao tem escopo a limitar: ali
 * quem decide continua sendo apenas a permissao do usuario.
 */
final class EnforceTokenAbilities
{
    public function handle(Request $request, Closure $next): Response
    {
        $user  = $request->user();
        $token = $user?->currentAccessToken();

        if ($token === null || $token instanceof TransientToken) {
            return $next($request);
        }

        foreach ($this->abilitiesDaRota($request) as $ability) {
            if (! $user->tokenCan($ability)) {
                abort(403, "O token de API nao tem escopo para {$ability}.");
            }
        }

        return $next($request);
    }

    /**
     * @return list<string>
     */
    private function abilitiesDaRota(Request $request): array
    {
        $rota = $request->route();

        if ($rota === null) {
            return [];
        }

        return collect($rota->gatherMiddleware())
            ->filter(static fn (mixed $m): bool => is_string($m) && str_starts_with($m, 'can:'))
            // can:ability,model -> apenas a ability interessa como escopo.
            ->map(static fn (string $m): string => explode(',', substr($m, 4))[0])
            ->filter(static fn (string $a): bool => $a !== '')
            ->unique()
            ->values()
            ->all();
    }
}
