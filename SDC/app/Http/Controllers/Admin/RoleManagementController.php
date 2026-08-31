<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\PermissionRegistrar;

class RoleManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:roles.view')->only(['index', 'show']);
        $this->middleware('can:roles.create')->only(['create', 'store']);
        $this->middleware('can:roles.edit')->only(['edit', 'update', 'syncPermissions']);
        $this->middleware('can:roles.delete')->only(['destroy']);
    }

    public function index(Request $request): Response
    {
        try {
            $query = Role::withCount(['users', 'permissions'])
                ->orderBy('hierarchy_level');

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $roles = $query->paginate(12)->withQueryString();

            $stats = [
                'total' => Role::count(),
                'active' => Role::where('is_active', true)->count(),
                'users_with_roles' => \DB::table('model_has_roles')
                    ->where('model_type', 'App\\Models\\User')
                    ->distinct('model_id')
                    ->count('model_id'),
            ];
        } catch (\Exception $e) {
            $roles = \App\Support\MockDataHelper::getRoles();
            $stats = \App\Support\MockDataHelper::getRoleStatistics();
        }

        return Inertia::render('Admin/Permissions/Roles/Index', [
            'roles' => $roles,
            'stats' => $stats,
            'filters' => $request->only(['search']),
        ]);
    }

    public function show(Role $role): Response
    {
        $role->load(['permissions', 'users']);

        return Inertia::render('Admin/Permissions/Roles/Show', [
            'role' => $role,
            'allPermissions' => Permission::orderBy('module')->orderBy('name')->get()->groupBy('module'),
        ]);
    }

    public function create(): Response
    {
        $availablePermissions = Permission::orderBy('name')->get();

        return Inertia::render('Admin/Permissions/Roles/Create', [
            'availablePermissions' => $availablePermissions,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:roles,slug',
            'description' => 'nullable|string',
            'hierarchy_level' => 'required|integer|min:0',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role = Role::create($validated);

        if (isset($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        $this->invalidarCachesDoCargo($role);

        return redirect()
            ->route('admin.permissions.roles.show', $role)
            ->with('success', 'Cargo criado com sucesso');
    }

    public function edit(Role $role): Response
    {
        if ($role->slug === 'super-admin') {
            abort(403, 'O cargo Super Admin nao pode ser editado');
        }

        $role->load('permissions');

        $availablePermissions = Permission::orderBy('name')->get();

        return Inertia::render('Admin/Permissions/Roles/Edit', [
            'role' => $role,
            'availablePermissions' => $availablePermissions,
        ]);
    }

    public function update(Request $request, Role $role)
    {
        if ($role->slug === 'super-admin') {
            return back()->with('error', 'O cargo Super Admin nao pode ser editado');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'hierarchy_level' => 'required|integer|min:0',
        ]);

        $role->update($validated);

        // Nao mexe em permissao, mas `hierarchy_level` viaja no payload do
        // Inertia (getUserData -> roles), entao o cache por usuario tambem
        // precisa cair.
        $this->invalidarCachesDoCargo($role);

        return redirect()
            ->route('admin.permissions.roles.show', $role)
            ->with('success', 'Cargo atualizado com sucesso');
    }

    public function syncPermissions(Request $request, Role $role)
    {
        if ($role->slug === 'super-admin') {
            return back()->with('error', 'As permissoes do Super Admin nao podem ser alteradas');
        }

        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->permissions()->sync($validated['permissions']);

        $this->invalidarCachesDoCargo($role);

        return redirect()
            ->route('admin.permissions.roles.show', $role)
            ->with('success', 'Permissoes atualizadas com sucesso');
    }

    /**
     * Invalida os caches que decidem o que os usuarios DESTE cargo enxergam.
     *
     * Mudanca em cargo e mais ampla que mudanca em usuario: atinge todos os que
     * o tem. Dois caches precisam cair:
     *
     * - o do Spatie, que serve a resolucao de `can()` (TTL de 1 hora);
     * - o `inertia_user_data_{id}` de CADA usuario do cargo, que o
     *   HandleInertiaRequests guarda por 300s e que carrega roles, permissoes
     *   efetivas e hierarchy_level -- ou seja, e o que monta a sidebar.
     *
     * Sem o segundo, o admin altera o cargo, o banco fica correto na hora, e
     * quem tem aquele cargo segue vendo o menu antigo por ate cinco minutos.
     *
     * `cursor()` de proposito: cargo com muitos usuarios nao cabe em memoria de
     * uma vez, e aqui so precisamos do id para montar a chave.
     */
    private function invalidarCachesDoCargo(Role $role): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role->users()->select('users.id')->cursor()->each(
            fn ($usuario) => Cache::forget("inertia_user_data_{$usuario->id}")
        );
    }

    public function destroy(Role $role)
    {
        if ($role->slug === 'super-admin') {
            return back()->with('error', 'O cargo Super Admin nao pode ser deletado');
        }

        if ($role->users()->count() > 0) {
            return back()->with('error', 'Nao e possivel deletar um cargo com usuarios atribuidos');
        }

        $role->delete();

        return redirect()
            ->route('admin.permissions.roles.index')
            ->with('success', 'Cargo deletado com sucesso');
    }
}
