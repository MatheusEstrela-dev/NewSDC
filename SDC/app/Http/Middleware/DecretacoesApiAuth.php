<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class DecretacoesApiAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        // Padrao de auth: sessao web (frontend logado) OU token pessoal Sanctum
        // (Bearer). O caminho legado X-PowerBI-Token foi aposentado em favor do
        // Sanctum Bearer (DB-backed, revogavel em /admin/permissions/users).
        if ($this->authenticateViaWebSession($request)) {
            return $next($request);
        }

        if ($this->authenticateViaSanctum($request)) {
            return $next($request);
        }

        return response()->json([
            'error'   => 'Unauthorized',
            'message' => 'Valid Bearer token required.',
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
}
