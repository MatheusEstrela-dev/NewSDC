<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PermissionAuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserStatusHistory;
use App\Modules\Compdec\Models\Orgao;
use App\Services\Auth\EmailChangeService;
use App\Services\Auth\OnboardingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\PermissionRegistrar;

class UserManagementController extends Controller
{
    public function __construct(
        private readonly OnboardingService $onboarding,
    ) {
        $this->middleware('can:users.view')->only(['index', 'show']);
        $this->middleware('can:users.create')->only(['create', 'store']);
        $this->middleware('can:users.edit')->only(['edit', 'update', 'syncRoles', 'syncPermissions']);
        $this->middleware('can:users.delete')->only(['destroy', 'reactivate']);
    }

    public function index(Request $request): Response
    {
        // A listagem so exibe a CONTAGEM de permissoes diretas; withCount evita
        // serializar todos os objetos de permissao de cada usuario no payload.
        $query = User::query()
            ->with('roles')
            ->withCount('permissions')
            ->select(['id', 'name', 'email', 'cpf', 'active', 'status', 'email_verified_at', 'created_at']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('slug', $request->role);
            });
        }

        $users = $query->orderBy('name')->paginate(15)->withQueryString();
        $roles = Role::select(['id', 'name', 'slug'])->orderBy('hierarchy_level')->get();

        return Inertia::render('Admin/Permissions/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'role']),
            'roles' => $roles,
        ]);
    }

    public function create(): Response
    {
        $roles = Role::select(['id', 'name', 'slug'])->orderBy('hierarchy_level')->get();

        return Inertia::render('Admin/Permissions/Users/Create', [
            'roles' => $roles,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'cpf' => 'required|string|size:11|unique:users,cpf',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
            'orgao_principal_id' => 'nullable|integer|exists:compdec_orgaos,id',
        ]);

        // Cria usuario em status='pending' com senha provisoria + envia e-mail
        // de onboarding. O usuario sera ativado quando trocar a senha no primeiro
        // acesso (vide FirstAccessController). Se nao trocar em 24h o scheduler
        // 'users:deactivate-expired-pending' desativa a conta.
        $user = $this->onboarding->createPendingUser([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'cpf' => $validated['cpf'],
            'orgao_principal_id' => $validated['orgao_principal_id'] ?? null,
        ]);

        $roles = Role::whereIn('id', $validated['roles'])->get();
        $user->syncRoles($roles);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('admin.permissions.users.index')
            ->with('success', "Usuario criado. E-mail de boas-vindas enviado para {$user->email}.");
    }

    public function show(User $user): Response
    {
        $user->load(['roles.permissions', 'permissions', 'orgaoPrincipal.municipio']);

        $user->direct_permissions = $user->permissions;
        $user->roles->each(function ($role) {
            $role->permissions_count = $role->permissions->count();
        });

        $tokens = $user->tokens->map(fn ($t) => [
            'id'           => $t->id,
            'name'         => $t->name,
            'abilities'    => $t->abilities,
            'last_used_at' => $t->last_used_at,
            'expires_at'   => $t->expires_at,
            'created_at'   => $t->created_at,
        ]);

        return Inertia::render('Admin/Permissions/Users/Show', [
            'user'         => $user,
            'tokens'       => $tokens,
            'newToken'     => session('new_token'),
            'newTokenName' => session('new_token_name'),
            'history'      => $this->buildHistory($user),
        ]);
    }

    /**
     * Monta uma timeline enxuta dos ultimos eventos relevantes do usuario,
     * mesclando audit_logs, permission_audit_log e user_status_histories.
     */
    private function buildHistory(User $user): array
    {
        $audits = AuditLog::where('table_name', 'users')
            ->where('row_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $permActions = PermissionAuditLog::where('entity_type', 'User')
            ->where('entity_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $statuses = UserStatusHistory::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $actorIds = $audits->pluck('user_id')
            ->merge($permActions->pluck('user_id'))
            ->merge($statuses->pluck('changed_by'))
            ->filter()
            ->unique()
            ->values();

        $actors = User::whereIn('id', $actorIds)->pluck('name', 'id');

        $items = collect();

        foreach ($audits as $a) {
            $entry = $this->mapAuditLog($a, $user, $actors);
            if ($entry) {
                $items->push($entry);
            }
        }

        foreach ($permActions as $p) {
            $entry = $this->mapPermissionAuditLog($p, $actors);
            if ($entry) {
                $items->push($entry);
            }
        }

        foreach ($statuses as $s) {
            $items->push([
                'id'          => 'status-' . $s->id,
                'kind'        => $s->new_status === 'active' ? 'activated' : 'deactivated',
                'summary'     => 'Status alterado para "' . $s->new_status . '"'
                                 . ($s->reason ? ' (' . $s->reason . ')' : ''),
                'actor_name'  => $actors[$s->changed_by] ?? 'Sistema',
                'occurred_at' => $s->created_at,
            ]);
        }

        return $items
            ->sortByDesc('occurred_at')
            ->take(10)
            ->values()
            ->all();
    }

    private function mapAuditLog(AuditLog $log, User $user, $actors): ?array
    {
        $actor = $log->user_id ? ($actors[$log->user_id] ?? 'Usuario #' . $log->user_id) : 'Sistema';
        $old = $log->old_values ?? [];
        $new = $log->new_values ?? [];

        if ($log->event === AuditLog::EVENT_INSERT) {
            return [
                'id'          => 'audit-' . $log->id,
                'kind'        => 'created',
                'summary'     => 'Usuario criado',
                'actor_name'  => $actor,
                'occurred_at' => $log->created_at,
            ];
        }

        if ($log->event === AuditLog::EVENT_DELETE) {
            return [
                'id'          => 'audit-' . $log->id,
                'kind'        => 'deleted',
                'summary'     => 'Usuario removido',
                'actor_name'  => $actor,
                'occurred_at' => $log->created_at,
            ];
        }

        if ($log->event !== AuditLog::EVENT_UPDATE) {
            return null;
        }

        $changed = [];
        foreach ($new as $field => $value) {
            if (!array_key_exists($field, $old) || $old[$field] !== $value) {
                $changed[$field] = ['from' => $old[$field] ?? null, 'to' => $value];
            }
        }

        if (isset($changed['password'])) {
            return [
                'id'          => 'audit-' . $log->id,
                'kind'        => 'password_reset',
                'summary'     => 'Senha redefinida',
                'actor_name'  => $actor,
                'occurred_at' => $log->created_at,
            ];
        }

        if (isset($changed['active'])) {
            $turnedOn = (bool) $changed['active']['to'];
            return [
                'id'          => 'audit-' . $log->id,
                'kind'        => $turnedOn ? 'activated' : 'deactivated',
                'summary'     => $turnedOn ? 'Usuario ativado' : 'Usuario desativado',
                'actor_name'  => $actor,
                'occurred_at' => $log->created_at,
            ];
        }

        if (isset($changed['orgao_principal_id'])) {
            $from = $this->orgaoInfo($changed['orgao_principal_id']['from']);
            $to = $this->orgaoInfo($changed['orgao_principal_id']['to']);

            $municipioChanged = $from['municipio_id'] !== $to['municipio_id']
                && !(is_null($from['municipio_id']) && is_null($to['municipio_id']));

            if ($municipioChanged) {
                $fromMun = $from['municipio'] ?? 'sem municipio';
                $toMun = $to['municipio'] ?? 'sem municipio';
                $summary = 'Remanejado de municipio: ' . $fromMun . ' -> ' . $toMun
                    . ' (orgao ' . $from['nome'] . ' -> ' . $to['nome'] . ')';
                $kind = 'municipio_changed';
            } else {
                $summary = 'Orgao principal alterado: ' . $from['nome'] . ' -> ' . $to['nome'];
                if (!empty($to['municipio'])) {
                    $summary .= ' (municipio: ' . $to['municipio'] . ')';
                }
                $kind = 'orgao_changed';
            }

            return [
                'id'          => 'audit-' . $log->id,
                'kind'        => $kind,
                'summary'     => $summary,
                'actor_name'  => $actor,
                'occurred_at' => $log->created_at,
            ];
        }

        $fields = array_keys($changed);
        if (empty($fields)) {
            return null;
        }

        $labels = [
            'name'   => 'nome',
            'email'  => 'email',
            'cpf'    => 'CPF',
            'status' => 'status',
        ];
        $labeled = array_map(fn ($f) => $labels[$f] ?? $f, $fields);

        return [
            'id'          => 'audit-' . $log->id,
            'kind'        => 'profile_updated',
            'summary'     => 'Dados atualizados (' . implode(', ', $labeled) . ')',
            'actor_name'  => $actor,
            'occurred_at' => $log->created_at,
        ];
    }

    private function mapPermissionAuditLog(PermissionAuditLog $log, $actors): ?array
    {
        $actor = $actors[$log->user_id] ?? 'Usuario #' . $log->user_id;

        $roleName = function ($state) {
            if (!is_array($state)) {
                return null;
            }
            return $state['role_name'] ?? $state['name'] ?? ($state['role_id'] ?? null);
        };

        $permissionName = function ($state) {
            if (!is_array($state)) {
                return null;
            }
            return $state['permission_name'] ?? $state['name'] ?? ($state['permission_id'] ?? null);
        };

        switch ($log->action) {
            case PermissionAuditLog::ACTION_ROLE_ASSIGNED:
                $name = $roleName($log->after_state) ?? $roleName($log->before_state);
                return [
                    'id'          => 'perm-' . $log->id,
                    'kind'        => 'role_granted',
                    'summary'     => $name ? 'Cargo "' . $name . '" atribuido' : 'Cargo atribuido',
                    'actor_name'  => $actor,
                    'occurred_at' => $log->created_at,
                ];

            case PermissionAuditLog::ACTION_ROLE_REMOVED:
                $name = $roleName($log->before_state) ?? $roleName($log->after_state);
                return [
                    'id'          => 'perm-' . $log->id,
                    'kind'        => 'role_revoked',
                    'summary'     => $name ? 'Cargo "' . $name . '" removido' : 'Cargo removido',
                    'actor_name'  => $actor,
                    'occurred_at' => $log->created_at,
                ];

            case PermissionAuditLog::ACTION_PERMISSION_ASSIGNED:
                $name = $permissionName($log->after_state) ?? $permissionName($log->before_state);
                return [
                    'id'          => 'perm-' . $log->id,
                    'kind'        => 'permission_granted',
                    'summary'     => $name ? 'Permissao "' . $name . '" atribuida' : 'Permissao atribuida',
                    'actor_name'  => $actor,
                    'occurred_at' => $log->created_at,
                ];

            case PermissionAuditLog::ACTION_PERMISSION_REMOVED:
                $name = $permissionName($log->before_state) ?? $permissionName($log->after_state);
                return [
                    'id'          => 'perm-' . $log->id,
                    'kind'        => 'permission_revoked',
                    'summary'     => $name ? 'Permissao "' . $name . '" removida' : 'Permissao removida',
                    'actor_name'  => $actor,
                    'occurred_at' => $log->created_at,
                ];
        }

        return null;
    }

    private function orgaoInfo($id): array
    {
        if (empty($id)) {
            return ['nome' => 'nenhum', 'municipio' => null, 'municipio_id' => null];
        }
        $orgao = Orgao::query()->with('municipio:id,nome')->find($id);
        if (!$orgao) {
            return ['nome' => 'Orgao #' . $id, 'municipio' => null, 'municipio_id' => null];
        }
        return [
            'nome'         => $orgao->nome,
            'municipio'    => $orgao->municipio?->nome,
            'municipio_id' => $orgao->municipio_id,
        ];
    }

    /**
     * Editar usuario com suas permissoes.
     *
     * LOGICA ADITIVA:
     * - role_permissions: permissoes herdadas do cargo (role_has_permissions)
     * - direct_permissions: permissoes atribuidas diretamente (model_has_permissions)
     * - effective_permissions: MERGE de cargo + diretas (o que vale de fato)
     */
    public function edit(User $user): Response
    {
        $user->load(['roles.permissions', 'permissions', 'orgaoPrincipal.municipio']);

        $directPermissions = $user->permissions->pluck('name')->toArray();
        $rolePermissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $effectivePermissions = array_values(array_unique(array_merge($rolePermissions, $directPermissions)));

        $user->direct_permissions = $user->permissions;
        $user->effective_permissions = $effectivePermissions;
        $user->role_permissions = $rolePermissions;

        $availableRoles = Role::withCount('permissions')->orderBy('hierarchy_level')->get();
        $availablePermissions = Permission::orderBy('name')->get();

        $availableOrgaos = Orgao::query()
            ->with('municipio:id,nome')
            ->orderBy('nome')
            ->get(['id', 'nome', 'tipo', 'municipio_id']);

        return Inertia::render('Admin/Permissions/Users/Edit', [
            'user' => $user,
            'availableRoles' => $availableRoles,
            'availablePermissions' => $availablePermissions,
            'availableOrgaos' => $availableOrgaos,
            'canEditSuperAdmin' => auth()->user()->hasRole('super-admin'),
            'canManageSensitive' => auth()->user()->hasAnyRole('super-admin', 'admin'),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'cpf' => 'required|string|size:11|unique:users,cpf,' . $user->id,
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
            'direct_permissions' => 'nullable|array',
            'direct_permissions.*' => 'exists:permissions,name',
            'password' => 'nullable|string|min:8|confirmed',
            'active' => 'sometimes|boolean',
            'orgao_principal_id' => 'nullable|integer|exists:compdec_orgaos,id',
        ]);

        $canSensitive = auth()->user()->hasAnyRole('super-admin', 'admin');

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'cpf' => $validated['cpf'],
        ];

        if ($canSensitive) {
            if (!empty($validated['password'])) {
                $payload['password'] = Hash::make($validated['password']);
            }
            if ($request->has('active')) {
                $active = $request->boolean('active');

                if (!$active && $user->id === auth()->id()) {
                    return back()->with('error', 'Voce nao pode desativar sua propria conta');
                }

                $payload['active'] = $active;
                $payload['status'] = $active ? 'active' : 'inactive';
            }
            if (array_key_exists('orgao_principal_id', $validated)) {
                $payload['orgao_principal_id'] = $validated['orgao_principal_id'];
            }
        }

        $oldStatus = $user->status ?? ($user->active ? 'active' : 'inactive');

        // Troca de e-mail pelo admin segue o MESMO fluxo de magic code
        // (sem bypass) — admin nao pode "forjar" posse de e-mail.
        if (isset($payload['email']) && $payload['email'] !== $user->email) {
            $newEmail = $payload['email'];
            unset($payload['email']);

            try {
                app(EmailChangeService::class)->requestChange(
                    $user, $newEmail, $request, byAdmin: auth()->user()
                );
                // Invalida cache para o user-alvo enxergar pending_email_change
                // assim que carregar a proxima pagina.
                Cache::forget("inertia_user_data_{$user->id}");
            } catch (\App\Exceptions\Auth\EmailChange\EmailAlreadyInUseException) {
                return back()->with('error', 'E-mail ja cadastrado em outro usuario.');
            } catch (\App\Exceptions\Auth\EmailChange\SameEmailException) {
                // silencioso
            }
        }

        $user->update($payload);

        if (array_key_exists('status', $payload) && $oldStatus !== $payload['status']) {
            UserStatusHistory::logStatusChange(
                $user,
                $oldStatus,
                $payload['status'],
                $payload['status'] === 'active'
                    ? 'Ativado via gerenciamento de usuarios'
                    : 'Desativado via gerenciamento de usuarios'
            );
        }

        if (isset($validated['roles'])) {
            $roles = Role::whereIn('id', $validated['roles'])->get();
            $user->syncRoles($roles);
        }

        if (array_key_exists('direct_permissions', $validated)) {
            $user->syncPermissions($validated['direct_permissions'] ?? []);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('admin.permissions.users.show', $user)
            ->with('success', 'Usuario atualizado com sucesso');
    }

    public function syncRoles(Request $request, User $user)
    {
        $validated = $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $roles = Role::whereIn('id', $validated['roles'])->get();
        $user->syncRoles($roles);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('admin.permissions.users.show', $user)
            ->with('success', 'Cargos atualizados com sucesso');
    }

    public function syncPermissions(Request $request, User $user)
    {
        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $permissionNames = Permission::whereIn('id', $validated['permissions'])->pluck('name')->toArray();
        $user->syncPermissions($permissionNames);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('admin.permissions.users.show', $user)
            ->with('success', 'Permissoes atualizadas com sucesso');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Voce nao pode excluir sua propria conta');
        }

        if ($user->hasRole('super-admin') && !auth()->user()->hasRole('super-admin')) {
            return back()->with('error', 'Apenas um Desenvolvedor pode excluir outro Desenvolvedor');
        }

        $user->delete();

        return redirect()
            ->route('admin.permissions.users.index')
            ->with('success', 'Usuario excluido com sucesso');
    }

    public function reactivate(User $user)
    {
        if (!auth()->user()->hasAnyRole('super-admin', 'admin') && !auth()->user()->can('users.delete')) {
            return back()->with('error', 'Voce nao tem permissao para reativar usuarios');
        }

        if ($user->trashed()) {
            $user->restore();
        } elseif ($user->active) {
            return back()->with('info', 'Usuario ja esta ativo');
        }

        $oldStatus = $user->status ?? 'inactive';

        $user->update([
            'active' => true,
            'status' => 'active',
        ]);

        UserStatusHistory::logStatusChange($user, $oldStatus, 'active', 'Reativado via gerenciamento de usuarios');

        return redirect()
            ->route('admin.permissions.users.index')
            ->with('success', 'Usuario reativado com sucesso');
    }
}
