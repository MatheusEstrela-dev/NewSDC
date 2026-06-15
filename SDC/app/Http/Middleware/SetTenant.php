<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\Tenancy\TenantContext;
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
 * Após resolver, o tenant é gravado no TenantContext (escopo de coroutine,
 * seguro sob Swoole). Modelo A: isolamento lógico por tenant_id via HasTenant;
 * sem troca de conexão por request (evita mutar config global entre coroutines).
 *
 * Rotas sem tenant específico (sistema central) passam sem restrição.
 */
class SetTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = null;

        try {
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

            if ($tenant && $request->hasSession()) {
                $request->session()->put('tenant_id', $tenant->id);
            }
        } catch (\Illuminate\Database\QueryException) {
            // Tabela tenants ainda nao existe (migration pendente) -- segue sem tenant
            $tenant = null;
        }

        // Grava no contexto da coroutine (nao no container compartilhado): sob
        // Swoole, app()->instance('tenant') vazaria entre requests concorrentes
        // no mesmo worker. Escreve SEMPRE (inclusive null) para nao herdar o
        // tenant da request anterior no modelo sequencial (RoadRunner).
        TenantContext::set($tenant);

        return $next($request);
    }
}
