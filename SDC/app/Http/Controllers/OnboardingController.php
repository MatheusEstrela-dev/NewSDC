<?php

namespace App\Http\Controllers;

use App\Services\Auth\OnboardingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Endpoints leves para acoes de onboarding disparadas pelo frontend
 * (notavelmente a conclusao/skip do tour Shepherd no Dashboard).
 */
class OnboardingController extends Controller
{
    public function __construct(
        private readonly OnboardingService $onboarding,
    ) {
    }

    public function completeTour(Request $request): JsonResponse
    {
        $user = $request->user();

        $this->onboarding->completeTour($user);

        // Garante que a flag show_welcome_tour caia no proximo carregamento.
        Cache::forget("inertia_user_data_{$user->id}");

        return response()->json(['ok' => true]);
    }
}
