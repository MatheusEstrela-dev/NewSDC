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
    /**
     * Memoizacao em processo (por worker Octane) do tenant resolvido, na frente
     * do cache Redis. Evita um GET no Redis a cada navegacao do mesmo tenant
     * dentro do worker. TTL igual ao Cache::remember (300s): a tolerancia a
     * stale do sistema continua sendo uma so, e rotas stateless (API/health)
     * deixam de tocar Postgres/Redis a cada request dentro da janela.
     *
     * @var array<int, array{tenant: ?Tenant, exp: float}>
     */
    private static array $tenantMemo = [];

    private const TENANT_MEMO_TTL = 300;

    private const MEMO_MAX_ENTRIES = 256;

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
                    $tenant = $this->resolveTenantCached((int) $tenantId);
                }
            }

            if (!$tenant) {
                $tenant = $this->resolveFromRequestCached($request);
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

    /**
     * Resolve o tenant memoizando em processo (por worker) na frente do Redis.
     */
    private function resolveTenantCached(int $tenantId): ?Tenant
    {
        $now = microtime(true);
        $hit = self::$tenantMemo[$tenantId] ?? null;
        if ($hit !== null && $hit['exp'] > $now) {
            return $hit['tenant'] === null ? null : clone $hit['tenant'];
        }

        $tenant = Cache::remember(
            "tenant:{$tenantId}",
            self::TENANT_MEMO_TTL,
            fn (): ?Tenant => Tenant::find($tenantId),
        );

        self::memoize(self::$tenantMemo, $tenantId, $tenant, $now);

        return $tenant;
    }

    /**
     * Memoizacao em processo (por worker) da resolucao por header/host.
     *
     * Sem isto, TODA request sem tenant_id na sessao (ex.: /api/health, rotas
     * API stateless) dispara `SELECT * FROM tenants WHERE dominio = ?` -- um
     * round-trip ao Postgres por request, que sob Azure (I/O gerenciado) domina
     * a latencia da cadeia api (Fase 1.2). O mapeamento host->tenant e
     * read-mostly; cacheamos por chave (X-Tenant header ou host), inclusive o
     * resultado null (negative cache). O caminho user-scoped NAO e cacheado por
     * host (varia por usuario).
     *
     * @var array<string, array{tenant: ?Tenant, exp: float}>
     */
    private static array $resolveMemo = [];

    private function resolveFromRequestCached(Request $request): ?Tenant
    {
        // Resolucao por usuario autenticado nao e cacheavel por host.
        if ($request->user()) {
            return Tenant::resolveFromRequest($request);
        }

        $slug = $request->header('X-Tenant');
        $key = $slug
            ? 'slug:' . hash('sha256', $slug)
            : 'host:' . hash('sha256', $request->getHost());
        $now = microtime(true);
        $hit = self::$resolveMemo[$key] ?? null;
        if ($hit !== null && $hit['exp'] > $now) {
            return $hit['tenant'] === null ? null : clone $hit['tenant'];
        }

        $tenant = Tenant::resolveFromRequest($request);
        self::memoize(self::$resolveMemo, $key, $tenant, $now);

        return $tenant;
    }

    /**
     * Mantem os dois memos de worker dentro do TTL e de um limite fixo.
     * A ordem do array fornece a entrada mais antiga para eviccao.
     *
     * @param array<int|string, array{tenant: ?Tenant, exp: float}> $memo
     */
    private static function memoize(array &$memo, int|string $key, ?Tenant $tenant, float $now): void
    {
        foreach ($memo as $memoKey => $entry) {
            if ($entry['exp'] <= $now) {
                unset($memo[$memoKey]);
            }
        }

        if (count($memo) >= self::MEMO_MAX_ENTRIES) {
            unset($memo[array_key_first($memo)]);
        }

        // O modelo guardado nunca deve ser o objeto entregue a request.
        $memo[$key] = ['tenant' => $tenant === null ? null : clone $tenant, 'exp' => $now + self::TENANT_MEMO_TTL];
    }
}
