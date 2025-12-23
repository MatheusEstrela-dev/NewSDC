<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Role;
use Illuminate\Console\Command;

class MakeSuperAdmin extends Command
{
    protected $signature = 'user:make-superadmin {email? : Email do usuário}';
    protected $description = 'Torna um usuário Super Admin';

    public function handle()
    {
        $email = $this->argument('email');

        if (!$email) {
            $email = $this->ask('Qual o email do usuário?');
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("Usuário com email '{$email}' não encontrado!");

            if ($this->confirm('Deseja criar este usuário?')) {
                $name = $this->ask('Nome do usuário');
                $password = $this->secret('Senha (mínimo 8 caracteres)');

                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => bcrypt($password),
                    'email_verified_at' => now(),
                ]);

                $this->info("Usuário '{$name}' criado com sucesso!");
            } else {
                return 0;
            }
        }

        $superAdminRole = Role::where('slug', 'super-admin')->first();

        if (!$superAdminRole) {
            $this->error('Cargo Super Admin não encontrado!');
            $this->info('Execute: php artisan db:seed --class=RolesAndPermissionsSeeder');
            return 1;
        }

        if ($user->hasRole('super-admin')) {
            $this->warn("Usuário '{$user->name}' já é Super Admin!");
            return 0;
        }

        $user->roles()->sync([$superAdminRole->id]);

        $this->info("✅ Usuário '{$user->name}' ({$user->email}) agora é Super Admin!");
        $this->info("Cargos: " . $user->roles->pluck('name')->join(', '));

        return 0;
    }
}
