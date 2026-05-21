<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\IntegrationTokenService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class DecretacoesApiAuth
{
    public function __construct(
        private readonly IntegrationTokenService $tokenService
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->authenticateViaWebSession($request)) {
            return $next($request);
        }

        if ($this->authenticateViaSanctum($request)) {
            return $next($request);
        }

        if ($this->authenticateViaPowerBIToken($request)) {
            return $next($request);
        }

        return response()->json([
            'error'   => 'Unauthorized',
            'message' => 'Valid Bearer token or X-PowerBI-Token required.',
        ], 401);
    }

    private function authenticateViaWebSession(Request $request): bool
    {
        $user = Auth::guard('web')->user();

        if ($user) {
            return true;
        }

        return false;
    }

    private function authenticateViaSanctum(Request $request): bool
    {
        Auth::guard('sanctum')->setRequest($request);
        $user = Auth::guard('sanctum')->user();

        if ($user) {
            Auth::setUser($user);
            return true;
        }

        return false;
    }

    private function authenticateViaPowerBIToken(Request $request): bool
    {
        $token = $request->header('X-PowerBI-Token');

        if (!$token) {
            return false;
        }

        $tokenData = $this->tokenService->validatePowerBIToken($token);

        if ($tokenData === null) {
            return false;
        }

        // Vincula o user dono do token ao request para que controllers
        // downstream (AsynchronousResponse, TraceController) consigam
        // isolar recursos por user. Tokens legados (sem user_id) continuam
        // passando sem user setado — comportamento anterior preservado.
        $userId = $tokenData['user_id'] ?? null;
        if ($userId !== null) {
            $user = \App\Models\User::find($userId);
            if ($user) {
                Auth::setUser($user);
            }
        }

        return true;
    }
}
