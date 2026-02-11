<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

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

        $user->roles()->sync($validated['roles']);

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

        return Inertia::render('Admin/Permissions/Users/Show', [
            'user' => $user,
        ]);
    }

    public function edit(User $user): Response
    {
        $user->load(['roles', 'permissions']);

        $user->direct_permissions = $user->permissions;

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
            $user->roles()->sync($validated['roles']);
        }

        if (isset($validated['direct_permissions'])) {
            $user->syncPermissions($validated['direct_permissions']);
        }

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

        $user->roles()->sync($validated['roles']);

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

        $user->permissions()->sync($validated['permissions']);

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
