<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PermissionAuditLog;
use App\Models\User;
use App\Services\Auth\TokenService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Emissao e revogacao de token de API pelo modulo de Permissionamento.
 *
 * Duas regras sustentam o escopo:
 *
 * 1. O token nasce com abilities explicitas, nunca com curinga. Sem escopo
 *    informado, herda as permissoes que o dono tem hoje -- um retrato, nao um
 *    vinculo: permissao concedida depois nao entra no token, e permissao
 *    retirada continua barrando, porque `can:` segue conferindo o usuario.
 * 2. O escopo pedido nunca excede as permissoes do dono. Um administrador com
 *    users.edit nao usa esta tela para fabricar acesso que o usuario nao tem.
 *
 * Quem faz a ability valer em runtime e o middleware EnforceTokenAbilities.
 */
class UserTokenController extends Controller
{
    /**
     * Sem opcao "sem expiracao": token de API eterno nao e defensavel, e a
     * expiracao gravada aqui e a unica fonte da verdade (config/sanctum.php
     * mantem 'expiration' nulo para nao encurtar o prazo por tras da tela).
     *
     * @var array<string, int>
     */
    private const PRAZOS_EM_DIAS = [
        '24h' => 1,
        '7d'  => 7,
        '30d' => 30,
        '90d' => 90,
    ];

    public function __construct(private TokenService $tokenService)
    {
        $this->middleware('can:users.edit');
    }

    public function store(Request $request, User $user): RedirectResponse
    {
        $permissoesDoDono = $user->getAllPermissions()->pluck('name')->values()->all();

        $validated = $request->validate([
            'name'        => 'required|string|max:60',
            'expires_in'  => ['required', Rule::in(array_keys(self::PRAZOS_EM_DIAS))],
            'abilities'   => 'sometimes|array|min:1',
            'abilities.*' => ['string', Rule::in($permissoesDoDono)],
        ], [
            'abilities.*.in' => 'O escopo do token nao pode exceder as permissoes do usuario.',
        ]);

        $abilities = $validated['abilities'] ?? $permissoesDoDono;

        if ($abilities === []) {
            return back()->withErrors([
                'abilities' => 'O usuario nao tem nenhuma permissao; um token seria inutil.',
            ]);
        }

        $expiresAt = Carbon::now()->addDays(self::PRAZOS_EM_DIAS[$validated['expires_in']]);

        $newToken = $user->createToken($validated['name'], $abilities, $expiresAt);

        PermissionAuditLog::logAction(
            userId: (int) $request->user()->id,
            action: PermissionAuditLog::ACTION_TOKEN_CREATED,
            entityType: 'User',
            entityId: (int) $user->id,
            afterState: [
                'token_id'   => $newToken->accessToken->id,
                'name'       => $validated['name'],
                'abilities'  => $abilities,
                'expires_at' => $expiresAt->toIso8601String(),
            ],
            reason: 'Token de API emitido pelo Permissionamento.',
        );

        return redirect()
            ->route('admin.permissions.users.show', $user)
            ->with('new_token', $newToken->plainTextToken)
            ->with('new_token_name', $validated['name']);
    }

    public function destroy(Request $request, User $user, int $tokenId): RedirectResponse
    {
        $token = $user->tokens()->find($tokenId);

        if ($token === null) {
            abort(404);
        }

        $retrato = [
            'token_id'  => $token->id,
            'name'      => $token->name,
            'abilities' => $token->abilities,
        ];

        if (! $this->tokenService->revokeToken($user, $tokenId)) {
            abort(404);
        }

        PermissionAuditLog::logAction(
            userId: (int) $request->user()->id,
            action: PermissionAuditLog::ACTION_TOKEN_REVOKED,
            entityType: 'User',
            entityId: (int) $user->id,
            beforeState: $retrato,
            reason: 'Token de API revogado pelo Permissionamento.',
        );

        return redirect()
            ->route('admin.permissions.users.show', $user)
            ->with('success', 'Token revogado com sucesso.');
    }
}
