<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Auth\Contracts\AuthServiceInterface;
use App\Services\Auth\TokenService;
use App\Services\Auth\UserRegistrationService;
use Illuminate\Support\Facades\Hash;

/**
 * Service Layer para Autenticação
 * Segue Single Responsibility Principle (SOLID)
 * Centraliza lógica de autenticação fora do Controller
 */
class AuthService implements AuthServiceInterface
{
    public function __construct(
        private TokenService $tokenService,
        private UserRegistrationService $userRegistrationService
    ) {}

    /**
     * Autenticar usuário com credenciais
     */
    public function authenticate(string $email, string $password): ?array
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return null;
        }

        return $this->buildAuthResponse($user);
    }

    /**
     * Registrar novo usuário
     */
    public function register(array $data): array
    {
        $user = $this->userRegistrationService->register($data);
        return $this->buildAuthResponse($user);
    }

    /**
     * Renovar token de autenticação
     */
    public function refreshToken(User $user, $currentToken): array
    {
        // Revogar token atual
        $currentToken->delete();

        // Criar novo token
        $token = $this->tokenService->createTokenForUser($user);

        return [
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
        ];
    }

    /**
     * Logout do usuário (revoga token atual)
     */
    public function logout($currentToken): void
    {
        $currentToken->delete();
    }

    /**
     * Logout de todos os dispositivos (revoga todos os tokens)
     */
    public function logoutAll(User $user): void
    {
        $user->tokens()->delete();
    }

    /**
     * Construir resposta de autenticação padronizada
     * Segue DRY (Don't Repeat Yourself)
     */
    private function buildAuthResponse(User $user): array
    {
        $token = $this->tokenService->createTokenForUser($user);

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'cpf' => $user->cpf,
                'roles' => $user->roles->pluck('slug'),
                'permissions' => $user->getAllPermissions(),
            ],
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
        ];
    }

    /**
     * Obter informações do usuário formatadas
     */
    public function getUserInfo(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'cpf' => $user->cpf,
            'email_verified_at' => $user->email_verified_at,
            'roles' => $user->roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'slug' => $role->slug,
                ];
            }),
            'permissions' => $user->getAllPermissions(),
        ];
    }
}
