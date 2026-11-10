<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class TreinamentoPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Fonte unica: config/permissions.php.
        foreach (config('permissions.modules.TREINAMENTO', []) as $acoes) {
            foreach ($acoes as $slug) {
                Permission::firstOrCreate(['name' => $slug, 'guard_name' => 'web']);
            }
        }
    }
}
