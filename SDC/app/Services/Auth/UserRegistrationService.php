<?php

namespace App\Services\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Service para registro de usuários
 * Segue Single Responsibility Principle
 * Responsável apenas por criação e registro de usuários
 */
class UserRegistrationService
{
    /**
     * Registrar novo usuário no sistema
     */
    public function register(array $data): User
    {
        // Criar usuário
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'cpf' => $data['cpf'],
            'password' => Hash::make($data['password']),
        ]);

        // Atribuir role padrão
        $this->assignDefaultRole($user);

        return $user;
    }

    /**
     * Atribuir role padrão ao usuário
     * Segue Open/Closed Principle - pode ser estendido sem modificação
     */
    private function assignDefaultRole(User $user, string $roleSlug = 'user'): void
    {
        $defaultRole = Role::where('slug', $roleSlug)->first();

        if ($defaultRole) {
            $user->assignRoles([$defaultRole->id]);
        }
    }
}
