<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class DemandasPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Fonte unica: config/permissions.php. Os slugs antigos (demandas.view-own,
        // demandas.manage...) nao sao mais usados por rota nenhuma.
        foreach (config('permissions.modules.DEMANDAS', []) as $acoes) {
            foreach ($acoes as $slug) {
                Permission::firstOrCreate(['name' => $slug, 'guard_name' => 'web']);
            }
        }
    }
}
