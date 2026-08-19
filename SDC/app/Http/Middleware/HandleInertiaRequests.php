<?php

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return getenv('INERTIA_VERSION') ?: parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        // Checa pelo model, nao pelo guard: em rotas do Portal do Cidadao
        // (guard "cidadao") o principal resolvido nao e um App\Models\User e
        // nao tem roles/permissions/relacoes esperadas abaixo. Esses dados
        // ja sao compartilhados separadamente via auth.cidadao.
        if (!$user instanceof User) {
            $user = null;
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => fn() => $user ? $this->getCachedUserData($user) : null,
                // Flags de onboarding NAO sao cacheadas: variam por carregamento
                // (must_change_password vira false apos o submit; show_welcome_tour
                // vem da session flash logo apos a troca de senha).
                'must_change_password' => fn() => $user ? (bool) $user->must_change_password : false,
                'show_welcome_tour' => fn() => $user && $this->shouldShowWelcomeTour($request, $user),
            ],
            // Nao vazar a arvore de ACL/permissoes em paginas publicas (login, etc).
            // Antes: era exposta no data-page do Inertia mesmo sem usuario autenticado.
            'acl' => fn() => $user ? $this->getCachedAclConfig() : (object) [],
            // Inertia::always() porque flash PRECISA viajar tambem no reload
            // parcial. Sem isso o `only:` do reload filtra o flash da resposta,
            // o cliente mantem o objeto da visita anterior, e o watcher do
            // FlashNotification torna a disparar: o aviso de "cadastrado com
            // sucesso" reaparecia a cada clique em filtro, ordenacao ou
            // paginacao, muito depois do cadastro.
            //
            // Marcado como always, toda resposta carrega o flash do PROPRIO
            // request -- vazio, no caso -- e sobrescreve o valor velho.
            //
            // As closures internas continuam preguicosas: evaluateProps()
            // percorre o array recursivamente depois de desembrulhar o
            // AlwaysProp.
            'flash' => Inertia::always([
                'success' => fn() => $request->session()->get('success'),
                'error'   => fn() => $request->session()->get('error'),
                'warning' => fn() => $request->session()->get('warning'),
                'info'    => fn() => $request->session()->get('info'),
            ]),
            // Badge do sino ja correto no primeiro paint, sem piscar zero e sem
            // um request extra por navegacao. Custo: um GET no Redis, resolvido
            // pelo ContadorNaoLidas (sem COUNT no banco no caminho quente).
            'notificacoes' => [
                'unread_count' => fn() => $user
                    ? app(\App\Modules\Notificacoes\Services\ContadorNaoLidas::class)->para($user)
                    : 0,
                // Como o cliente deve se atualizar: 'auto' tenta websocket e cai
                // para polling sozinho. Ver useNotifications no frontend.
                'update_mode' => fn() => $user ? ($user->notification_update_mode ?? 'auto') : 'polling',
            ],
        ];
    }

    /**
     * Mostra o tour quando o usuario acabou de concluir o primeiro acesso
     * (flash da session) OU quando ainda nao concluiu o tour e ja esta ativo.
     */
    protected function shouldShowWelcomeTour(Request $request, $user): bool
    {
        if ($request->session()->get('show_welcome_tour') === true) {
            return true;
        }

        return $user->status === 'active'
            && $user->welcome_tour_completed_at === null
            && $user->must_change_password === false;
    }

    /**
     * Retorna dados do usuario autenticado com cache de 5 minutos.
     * Evita queries repetitivas de roles/permissions a cada navegacao SPA.
     */
    protected function getCachedUserData($user): array
    {
        $cacheKey = "inertia_user_data_{$user->id}";

        return Cache::remember($cacheKey, 300, function () use ($user) {
            $user->loadMissing('activeEmailChangeRequest');
            // Eager load roles+permissions se nao carregados
            if (!$user->relationLoaded('roles')) {
                $user->load(['roles.permissions', 'permissions']);
            }
            return $this->getUserData($user);
        });
    }

    /**
     * Retorna dados do usuario autenticado.
     */
    protected function getUserData($user): array
    {
        $roles = [];
        if (method_exists($user, 'roles')) {
            $roles = $user->roles->map(fn($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'hierarchy_level' => $role->hierarchy_level,
            ])->values()->toArray();
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'cpf' => $user->cpf ?? null,
            'email' => $user->email ?? null,
            'is_super_admin' => method_exists($user, 'hasRole') ? $user->hasRole('super-admin') : false,
            'roles' => $roles,
            'role_names' => method_exists($user, 'getRoleNames') ? $user->getRoleNames()->values() : [],
            'permissions' => $this->getEffectivePermissions($user),
            'hierarchy_level' => method_exists($user, 'getHierarchyLevel')
                ? $user->getHierarchyLevel()
                : 99,
            'pending_email_change' => $this->buildPendingEmailChange($user),
        ];
    }

    /**
     * Retorna as permissoes efetivas do usuario.
     * LOGICA ADITIVA: Permissoes do CARGO + Permissoes DIRETAS = Efetivas
     *
     * Tabelas envolvidas:
     * - role_has_permissions: permissoes do cargo
     * - model_has_permissions: permissoes diretas do usuario
     * - model_has_roles: cargos atribuidos ao usuario
     */
    protected function getEffectivePermissions($user): array
    {
        if (!method_exists($user, 'permissions') || !method_exists($user, 'roles')) {
            return [];
        }

        // Usar relationships ja eager-loaded para evitar N+1
        $rolePermissions = $user->roles
            ->flatMap(fn($role) => $role->permissions->pluck('name'))
            ->toArray();

        $directPermissions = $user->permissions->pluck('name')->toArray();

        return array_values(array_unique(array_merge($rolePermissions, $directPermissions)));
    }

    /**
     * Retorna configuracao ACL com cache de 10 minutos.
     * Dados de config raramente mudam - nao precisam ser lidos do disco a cada request.
     */
    protected function getCachedAclConfig(): array
    {
        return Cache::remember('inertia_acl_config', 600, function () {
            return $this->getAclConfig();
        });
    }

    /**
     * Retorna configuracao ACL do config/permissions.php para o frontend.
     */
    protected function getAclConfig(): array
    {
        return [
            'levels' => config('permissions.levels', []),
            'modules' => config('permissions.modules', []),
            'protected_roles' => config('permissions.protected_roles', []),
            'immutable_permissions' => config('permissions.immutable_permissions', []),
            'default_level' => config('permissions.default_level', 99),
        ];
    }

    /**
     * Snapshot do pedido de troca de e-mail ativo, para o frontend
     * decidir se monta o popup de verificacao.
     *
     * Retorna null quando nao ha pedido pending — assim o front pode
     * tratar com `v-if="user.pending_email_change"`.
     */
    protected function buildPendingEmailChange($user): ?array
    {
        $ecr = $user->activeEmailChangeRequest;

        if (!$ecr || !$ecr->isPending()) {
            return null;
        }

        $resendCooldown = \App\Models\EmailChangeRequest::RESEND_COOLDOWN_SECONDS;
        $resendAvailableAt = $ecr->last_resend_at
            ? $ecr->last_resend_at->copy()->addSeconds($resendCooldown)
            : null;

        return [
            'id'                   => $ecr->id,
            'new_email_masked'     => $this->maskEmail($ecr->new_email),
            'current_email_masked' => $this->maskEmail($ecr->current_email),
            'expires_at'           => $ecr->expires_at->toIso8601String(),
            'attempts_remaining'   => \App\Models\EmailChangeRequest::MAX_ATTEMPTS - $ecr->code_attempts,
            'resend_available_at'  => $resendAvailableAt?->toIso8601String(),
            'resends_remaining'    => \App\Models\EmailChangeRequest::MAX_RESENDS_PER_REQUEST - $ecr->resend_count,
            'requested_by_admin'   => $ecr->requested_by_admin_id !== null,
        ];
    }

    /**
     * "matheus.estrela@gmail.com" -> "ma***@gmail.com"
     */
    protected function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2) + ['', ''];
        if ($local === '' || $domain === '') {
            return $email;
        }
        return substr($local, 0, 2) . '***@' . $domain;
    }
}
