<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\PermissionRegistrar;

class UserManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:users.view')->only(['index', 'show']);
        $this->middleware('can:users.create')->only(['create', 'store']);
        $this->middleware('can:users.edit')->only(['edit', 'update', 'syncRoles', 'syncPermissions']);
        $this->middleware('can:users.delete')->only(['destroy']);
    }

    public function index(Request $request): Response
    {
        $query = User::query()
            ->with(['roles', 'permissions'])
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
            'password' => 'required|string|min:8|confirmed',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'cpf' => $validated['cpf'],
            'password' => bcrypt($validated['password']),
            'active' => true,
        ]);

        $roleNames = Role::whereIn('id', $validated['roles'])->pluck('name')->toArray();
        $user->syncRoles($roleNames);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('admin.permissions.users.index')
            ->with('success', 'Usuario criado com sucesso');
    }

    public function show(User $user): Response
    {
        $user->load(['roles.permissions', 'permissions']);

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
        ]);
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
        $user->load(['roles.permissions', 'permissions']);

        $directPermissions = $user->permissions->pluck('name')->toArray();
        $rolePermissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $effectivePermissions = array_values(array_unique(array_merge($rolePermissions, $directPermissions)));

        $user->direct_permissions = $user->permissions;
        $user->effective_permissions = $effectivePermissions;
        $user->role_permissions = $rolePermissions;

        $availableRoles = Role::withCount('permissions')->orderBy('hierarchy_level')->get();
        $availablePermissions = Permission::orderBy('name')->get();

        return Inertia::render('Admin/Permissions/Users/Edit', [
            'user' => $user,
            'availableRoles' => $availableRoles,
            'availablePermissions' => $availablePermissions,
            'canEditSuperAdmin' => auth()->user()->hasRole('super-admin'),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
            'direct_permissions' => 'nullable|array',
            'direct_permissions.*' => 'exists:permissions,name',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if (isset($validated['roles'])) {
            $roleNames = Role::whereIn('id', $validated['roles'])->pluck('name')->toArray();
            $user->syncRoles($roleNames);
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

        $roleNames = Role::whereIn('id', $validated['roles'])->pluck('name')->toArray();
        $user->syncRoles($roleNames);

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
            return back()->with('error', 'Voce nao pode deletar sua propria conta');
        }

        if ($user->hasRole('super-admin')) {
            return back()->with('error', 'Super Admins nao podem ser deletados');
        }

        $user->delete();

        return redirect()
            ->route('admin.permissions.users.index')
            ->with('success', 'Usuario deletado com sucesso');
    }
}
