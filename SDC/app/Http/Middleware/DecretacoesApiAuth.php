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

        return $tokenData !== null;
    }
}
