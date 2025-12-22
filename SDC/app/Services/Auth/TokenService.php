<?php

namespace App\Services\Auth;

use App\Models\User;
use Laravel\Sanctum\NewAccessToken;

/**
 * Service para gerenciamento de tokens
 * Segue Single Responsibility Principle
 * Responsável apenas por operações com tokens
 */
class TokenService
{
    /**
     * Criar token com abilities para o usuário
     */
    public function createTokenForUser(User $user, string $tokenName = 'auth-token'): NewAccessToken
    {
        return $user->createTokenWithAbilities($tokenName);
    }

    /**
     * Revogar token específico
     */
    public function revokeToken(User $user, int $tokenId): bool
    {
        $token = $user->tokens()->find($tokenId);

        if (!$token) {
            return false;
        }

        $token->delete();
        return true;
    }

    /**
     * Revogar todos os tokens do usuário
     */
    public function revokeAllTokens(User $user): void
    {
        $user->tokens()->delete();
    }

    /**
     * Listar tokens ativos do usuário
     */
    public function getUserTokens(User $user): array
    {
        return $user->tokens->map(function ($token) {
            return [
                'id' => $token->id,
                'name' => $token->name,
                'abilities' => $token->abilities,
                'last_used_at' => $token->last_used_at,
                'created_at' => $token->created_at,
            ];
        })->toArray();
    }
}
