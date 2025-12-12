<?php

namespace App\Services\Auth\Contracts;

use App\Models\User;

/**
 * Interface para AuthService
 * Segue Interface Segregation Principle (SOLID)
 * Permite múltiplas implementações de autenticação
 */
interface AuthServiceInterface
{
    /**
     * Autenticar usuário com credenciais
     */
    public function authenticate(string $email, string $password): ?array;

    /**
     * Registrar novo usuário
     */
    public function register(array $data): array;

    /**
     * Renovar token de autenticação
     */
    public function refreshToken(User $user, $currentToken): array;

    /**
     * Logout do usuário
     */
    public function logout($currentToken): void;

    /**
     * Logout de todos os dispositivos
     */
    public function logoutAll(User $user): void;

    /**
     * Obter informações do usuário
     */
    public function getUserInfo(User $user): array;
}
