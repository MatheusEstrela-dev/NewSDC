<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PermissionManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:permissions.view');
    }

    public function index(Request $request): Response
    {
        $query = Permission::withCount(['roles'])
            ->orderBy('name');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('module') && $request->module !== '') {
            $query->where('name', 'like', $request->module . '.%');
        }

        $permissions = $query->get();

        $stats = [
            'total' => Permission::count(),
            'modules' => Permission::select('name')
                ->get()
                ->map(fn($p) => explode('.', $p->name)[0])
                ->unique()
                ->count(),
            'active' => Permission::count(),
        ];

        return Inertia::render('Admin/Permissions/Permissions/Index', [
            'permissions' => $permissions,
            'stats' => $stats,
            'filters' => $request->only(['search', 'module']),
        ]);
    }

    public function show(Permission $permission): Response
    {
        $permission->load('roles');

        return Inertia::render('Admin/Permissions/Permissions/Show', [
            'permission' => $permission,
        ]);
    }
}
