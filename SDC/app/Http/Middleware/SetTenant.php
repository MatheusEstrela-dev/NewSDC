<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $tenant = Tenant::resolveFromRequest($request);

        if ($tenant) {
            // Vincula o tenant ao container para uso no HasTenant trait
            app()->instance('tenant', $tenant);

            // Se o tenant usa banco próprio, reconfigura a conexão tenancy
            $tenant->getDatabaseConnection();

            // Compartilha o tenant com todas as views Inertia
            if ($request->hasSession()) {
                $request->session()->put('tenant_id', $tenant->id);
            }
        }

        return $next($request);
    }
}
