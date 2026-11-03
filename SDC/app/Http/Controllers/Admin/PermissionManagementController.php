<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        try {
            $query = Permission::withCount(['roles'])
                ->orderBy('name');

            // ILIKE no Postgres: LIKE diferencia maiuscula, e "notas tecn" nao
            // achava "Notas Tecnicas" na descricao.
            $like = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';

            if ($request->filled('search')) {
                $search = (string) $request->search;
                $query->where(function ($q) use ($search, $like) {
                    $q->where('name', $like, "%{$search}%")
                        ->orWhere('description', $like, "%{$search}%");
                });
            }

            if ($request->has('module') && $request->module !== '') {
                $query->where('name', 'like', $request->module . '.%');
            }

            $permissions = $query->get();

            // Mock so quando a tabela inteira esta vazia (ambiente sem seed). Busca
            // sem resultado e resposta legitima: antes caia aqui e a tela mostrava
            // as 30 permissoes falsas no lugar de "nenhum resultado".
            if ($permissions->isEmpty() && ! Permission::query()->exists()) {
                throw new \Exception("No permissions found, using mocks.");
            }

            $stats = [
                'total' => Permission::count(),
                'modules' => Permission::select('name')
                    ->get()
                    ->map(fn($p) => explode('.', $p->name)[0])
                    ->unique()
                    ->count(),
                'active' => Permission::count(),
            ];
        } catch (\Exception $e) {
            $permissions = \App\Support\MockDataHelper::getPermissions();
            $stats = \App\Support\MockDataHelper::getPermissionStatistics();
        }

        return Inertia::render('Admin/Permissions/Permissions/Index', [
            'permissions' => $permissions,
            'stats' => $stats,
            'filters' => $request->only(['search', 'module']),
            'modulos' => $this->rotulosDeModulo(),
        ]);
    }

    public function show(Permission $permission): Response
    {
        $permission->load('roles');

        return Inertia::render('Admin/Permissions/Permissions/Show', [
            'permission' => $permission,
        ]);
    }
    /**
     * Prefixo de slug -> rotulo do modulo, derivado de config/permissions.php.
     *
     * A tela mantinha um catalogo HARDCODED de 9 modulos, enquanto o config tem
     * 17: modulo fora dessa lista aparecia como o prefixo em caixa alta e
     * "Modulo do sistema" na descricao -- foi o que aconteceu com CISTERNAS e
     * DEMANDAS. Derivar do config faz a tela acompanhar quem entra depois, sem
     * ninguem lembrar de editar o Vue.
     *
     * O mapa e por PREFIXO porque um modulo do config pode agrupar varios: o
     * SISTEMA reune users, roles, permissions e system.
     *
     * @return array<string, string>
     */
    private function rotulosDeModulo(): array
    {
        $rotulos = [];

        foreach ((array) config('permissions.modules', []) as $modulo => $grupos) {
            $rotulo = str_replace('_', ' ', (string) $modulo);

            foreach ((array) $grupos as $acoes) {
                foreach ((array) $acoes as $slug) {
                    $prefixo = explode('.', (string) $slug)[0];

                    $rotulos[$prefixo] ??= $rotulo;
                }
            }
        }

        return $rotulos;
    }

}
