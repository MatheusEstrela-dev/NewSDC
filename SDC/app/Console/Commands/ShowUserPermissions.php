<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ShowUserPermissions extends Command
{
    protected $signature = 'user:permissions {email?}';
    protected $description = 'Mostra as permissões de um usuário';

    public function handle()
    {
        $email = $this->argument('email');

        if (!$email) {
            $user = User::first();
            if (!$user) {
                $this->error('Nenhum usuário encontrado no sistema');
                return 1;
            }
        } else {
            $user = User::where('email', $email)->first();
            if (!$user) {
                $this->error("Usuário com email '{$email}' não encontrado");
                return 1;
            }
        }

        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║              INFORMAÇÕES DO USUÁRIO                         ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $this->line("  <fg=cyan>Nome:</>     {$user->name}");
        $this->line("  <fg=cyan>Email:</>    {$user->email}");
        $this->line("  <fg=cyan>ID:</>       {$user->id}");
        $this->newLine();

        // Roles
        $roles = $user->roles()->orderBy('hierarchy_level')->get();

        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║                    ROLES (CARGOS)                           ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->newLine();

        if ($roles->isEmpty()) {
            $this->warn('  ⚠ Nenhuma role atribuída');
        } else {
            foreach ($roles as $role) {
                $permissionsCount = $role->permissions()->count();
                $this->line("  <fg=green>✓</> {$role->name} <fg=gray>({$role->slug}) - {$permissionsCount} permissões</>");
            }
        }
        $this->newLine();

        // Permissões diretas
        $directPermissions = $user->permissions()->orderBy('name')->get();

        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║                PERMISSÕES DIRETAS                           ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->newLine();

        if ($directPermissions->isEmpty()) {
            $this->warn('  ⚠ Nenhuma permissão direta');
        } else {
            foreach ($directPermissions as $permission) {
                $this->line("  <fg=green>✓</> {$permission->name} <fg=gray>({$permission->slug})</>");
            }
        }
        $this->newLine();

        // Todas as permissões (via roles + diretas)
        $allPermissions = $user->getAllPermissions()->sortBy('name');

        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║          TODAS AS PERMISSÕES (ROLES + DIRETAS)              ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->newLine();

        if ($allPermissions->isEmpty()) {
            $this->error('  ✗ Este usuário não possui nenhuma permissão!');
        } else {
            $this->line("  <fg=cyan>Total:</> {$allPermissions->count()} permissões");
            $this->newLine();

            // Agrupar por categoria
            $grouped = $allPermissions->groupBy(function ($permission) {
                $parts = explode('.', $permission->slug);
                return $parts[0] ?? 'other';
            });

            foreach ($grouped as $category => $permissions) {
                $this->line("  <fg=yellow>{$category}:</>");
                foreach ($permissions as $permission) {
                    $this->line("    <fg=green>✓</> {$permission->name} <fg=gray>({$permission->slug})</>");
                }
                $this->newLine();
            }
        }

        return 0;
    }
}
