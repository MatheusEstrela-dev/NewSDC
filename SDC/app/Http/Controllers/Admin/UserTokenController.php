<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\TokenService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserTokenController extends Controller
{
    public function __construct(private TokenService $tokenService)
    {
        $this->middleware('can:users.edit');
    }

    public function store(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:60',
            'expires_in' => 'required|in:24h,7d,30d,never',
        ]);

        $expiresAt = match ($validated['expires_in']) {
            '24h'   => Carbon::now()->addHours(24),
            '7d'    => Carbon::now()->addDays(7),
            '30d'   => Carbon::now()->addDays(30),
            'never' => null,
        };

        $newToken = $user->createToken($validated['name'], ['*'], $expiresAt);

        return redirect()
            ->route('admin.permissions.users.show', $user)
            ->with('new_token', $newToken->plainTextToken)
            ->with('new_token_name', $validated['name']);
    }

    public function destroy(User $user, int $tokenId): RedirectResponse
    {
        \Illuminate\Support\Facades\Log::info("Tentando revogar token {$tokenId} do usuario {$user->id}");
        $revoked = $this->tokenService->revokeToken($user, $tokenId);

        if (!$revoked) {
            \Illuminate\Support\Facades\Log::error("Falha ao revogar token {$tokenId} do usuario {$user->id}");
            abort(404);
        }

        return redirect()
            ->route('admin.permissions.users.show', $user)
            ->with('success', 'Token revogado com sucesso.');
    }
}
