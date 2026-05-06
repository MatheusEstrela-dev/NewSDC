<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateTestUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-test-user {--fix : Corrigir usuário existente se houver problemas}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cria ou verifica o usuário de teste (CPF: 12345678900, Senha: password)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cpf = '12345678900';
        $password = 'password';

        // Buscar usuário existente (pode estar com formatação)
        $user = User::where('cpf', $cpf)
            ->orWhere('cpf', 'like', '%' . $cpf . '%')
            ->first();

        if ($user) {
            $this->info(" Usuário encontrado (ID: {$user->id})");

            // Verificar e corrigir CPF
            if ($user->cpf !== $cpf) {
                $this->warn("  CPF incorreto: '{$user->cpf}' (length: " . strlen($user->cpf) . ")");
                if ($this->option('fix')) {
                    $user->cpf = $cpf;
                    $user->save();
                    $this->info(" CPF corrigido para: '{$cpf}'");
                }
            } else {
                $this->info(" CPF correto: '{$user->cpf}'");
            }

            // Verificar senha
            $passwordOk = Hash::check($password, $user->password);
            if (!$passwordOk) {
                $this->warn(" Senha '{$password}' não confere");
                if ($this->option('fix')) {
                    $user->password = Hash::make($password);
                    $user->save();
                    $this->info(" Senha corrigida");
                }
            } else {
                $this->info("Senha '{$password}' está correta");
            }

            // Exibir informações
            $this->newLine();
            $this->info(" Dados do usuário:");
            $this->line("   ID: {$user->id}");
            $this->line("   Nome: {$user->name}");
            $this->line("   Email: {$user->email}");
            $this->line("   CPF: '{$user->cpf}' (length: " . strlen($user->cpf) . ")");
            $this->line("   Senha '{$password}': " . (Hash::check($password, $user->password) ? 'CORRETA ' : 'INCORRETA '));

            if (!$this->option('fix') && ($user->cpf !== $cpf || !Hash::check($password, $user->password))) {
                $this->newLine();
                $this->warn(" Execute com --fix para corrigir automaticamente:");
                $this->line("   php artisan app:create-test-user --fix");
            }

            return 0;
        }

        // Criar novo usuário
        $this->info("Criando novo usuário de teste...");

        $user = User::create([
            'name' => 'Admin Geral',
            'email' => 'admin@defesa.mg.gov.br',
            'cpf' => $cpf,
            'password' => Hash::make($password),
        ]);

        $this->info(" Usuário criado com sucesso!");
        $this->newLine();
        $this->info("   Credenciais de login:");
        $this->line("   CPF: {$cpf}");
        $this->line("   Senha: {$password}");

        return 0;
    }
}
