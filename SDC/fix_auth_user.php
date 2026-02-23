<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$cpf = '12345678900';
$password = 'password';

// Tenta encontrar o usuário pelo CPF
$user = User::where('cpf', $cpf)->first();

if ($user) {
    echo "Usuário encontrado (ID: {$user->id}). Atualizando credenciais...\n";
    $user->password = Hash::make($password);
    $user->active = true;
    $user->save();
    echo "SUCESSO: Senha redefinida e usuário ativado.\n";
} else {
    echo "Usuário não encontrado. Criando novo usuário...\n";
    try {
        $user = User::create([
            'name' => 'Usuário Teste (Debug)',
            'email' => 'teste.12345678900@sdc.local', // Email fictício para o login
            'cpf' => $cpf,
            'password' => Hash::make($password),
            'active' => true,
            // 'orgao_principal_id' => 1, // Assumindo que exista um órgão ID 1, caso seja obrigatório
        ]);
        echo "SUCESSO: Usuário criado com CPF $cpf e senha '$password'.\n";
    } catch (
Exception $e) {
        echo "ERRO ao criar usuário: " . $e->getMessage() . "\n";
    }
}

