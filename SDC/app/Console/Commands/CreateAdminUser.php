<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create-admin
                            {--email=admin@defesa.mg.gov.br : Email do administrador}
                            {--name=Admin Geral : Nome do administrador}
                            {--cpf=12345678900 : CPF do administrador}
                            {--password=password : Senha do administrador}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cria o usuário administrador principal com role super-admin';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->option('email');
        $name = $this->option('name');
        $cpf = $this->option('cpf');
        $password = $this->option('password');

        $this->info('🔧 Criando usuário administrador...');
        $this->newLine();

        // Verificar se já existe
        $existingUser = User::where('email', $email)
            ->orWhere('cpf', $cpf)
            ->first();

        if ($existingUser) {
            $this->warn("⚠️  Usuário já existe!");
            $this->table(
                ['Campo', 'Valor'],
                [
                    ['ID', $existingUser->id],
                    ['Nome', $existingUser->name],
                    ['Email', $existingUser->email],
                    ['CPF', $existingUser->cpf],
                    ['Roles', $existingUser->roles->pluck('name')->implode(', ')],
                ]
            );

            if (!$this->confirm('Deseja atualizar este usuário?', false)) {
                $this->info('Operação cancelada.');
                return self::FAILURE;
            }

            $user = $existingUser;
            $user->update([
                'name' => $name,
                'email' => $email,
                'cpf' => $cpf,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]);

            $this->info('✅ Usuário atualizado!');
        } else {
            // Criar novo usuário
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'cpf' => $cpf,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]);

            $this->info('✅ Usuário criado com sucesso!');
        }

        // Atribuir role super-admin
        $guard = config('auth.defaults.guard', 'web');
        $superAdminRole = Role::where('slug', 'super-admin')
            ->where('guard_name', $guard)
            ->first();

        if (!$superAdminRole) {
            $this->error('❌ Role super-admin não encontrada!');
            $this->warn('Execute primeiro: php artisan db:seed --class=RolesAndPermissionsSeeder');
            return self::FAILURE;
        }

        // Remover todas as roles anteriores e atribuir super-admin
        $user->syncRoles([$superAdminRole]);

        $this->newLine();
        $this->info('✅ Role super-admin atribuída!');
        $this->newLine();

        // Verificar permissões
        $totalPermissions = $user->getAllPermissions()->count();

        $this->table(
            ['Campo', 'Valor'],
            [
                ['ID', $user->id],
                ['Nome', $user->name],
                ['Email', $user->email],
                ['CPF', $user->cpf],
                ['Role', 'super-admin'],
                ['Total de Permissões', $totalPermissions],
            ]
        );

        $this->newLine();
        $this->info('🎉 Administrador configurado com sucesso!');
        $this->warn('⚠️  IMPORTANTE: Altere a senha no primeiro login!');
        $this->newLine();

        return self::SUCCESS;
    }
}
