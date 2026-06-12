<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware SetTenant — resolve e ativa o tenant para cada request.
 *
 * Estratégia de resolução (em ordem de prioridade):
 *   1. Header X-Tenant (slug)  → útil para API/mobile
 *   2. Subdomínio da request   → ex: compdec.newsdc.gov.br
 *   3. tenant_id do usuário autenticado
 *
 * Após resolver, o tenant é:
 *   - Armazenado no container (app('tenant'))
 *   - A conexão 'tenancy' é configurada com o banco do tenant (se multi-db)
 *
 * Rotas sem tenant específico (sistema central) passam sem restrição.
 */
class SetTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $tenant = null;

            // Na sessao fica apenas o tenant_id (escalar): serializar o model
            // inteiro no Redis pesa a sessao e congela dados stale do tenant.
            if ($request->hasSession()) {
                $request->session()->forget('resolved_tenant');

                $tenantId = $request->session()->get('tenant_id');

                if ($tenantId) {
                    $tenant = Cache::remember(
                        "tenant:{$tenantId}",
                        300,
                        fn (): ?Tenant => Tenant::find($tenantId),
                    );
                }
            }

            if (!$tenant) {
                $tenant = Tenant::resolveFromRequest($request);
            }

            if ($tenant) {
                app()->instance('tenant', $tenant);
                $tenant->getDatabaseConnection();

                if ($request->hasSession()) {
                    $request->session()->put('tenant_id', $tenant->id);
                }
            }
        } catch (\Illuminate\Database\QueryException) {
            // Tabela tenants ainda nao existe (migration pendente) -- continua sem tenant
        }

        return $next($request);
    }
}
