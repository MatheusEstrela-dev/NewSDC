<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\FirstAccessRequest;
use App\Providers\RouteServiceProvider;
use App\Services\Auth\OnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Fluxo de primeiro acesso: o usuario chega aqui logo apos logar com a senha
 * provisoria (must_change_password=true). Ate substitui-la, todas as outras
 * rotas autenticadas sao bloqueadas pelo middleware EnsurePasswordChanged.
 */
class FirstAccessController extends Controller
{
    public function __construct(
        private readonly OnboardingService $onboarding,
    ) {
    }

    public function show(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        // Defesa em profundidade: se o middleware falhar, ainda assim nao
        // exibimos a tela para quem nao precisa trocar senha.
        if (!$user || !$user->must_change_password) {
            return redirect()->intended(RouteServiceProvider::HOME);
        }

        return Inertia::render('Auth/FirstAccess', [
            'userName' => $user->name,
            'expiresAt' => optional($user->pending_expires_at)->toIso8601String(),
        ]);
    }

    public function store(FirstAccessRequest $request): RedirectResponse
    {
        $user = $request->user();

        $this->onboarding->completeFirstAccess($user, $request->string('password')->value());

        // Cache do payload Inertia precisa ser invalidado pois os campos
        // status/must_change_password/welcome_tour_completed_at mudaram.
        Cache::forget("inertia_user_data_{$user->id}");

        // Sinaliza para o frontend disparar o tour Shepherd no proximo render
        // (consumido em HandleInertiaRequests::share -> flash.show_welcome_tour).
        $request->session()->flash('show_welcome_tour', true);

        return redirect()->intended(RouteServiceProvider::HOME)->with(
            'success',
            'Senha atualizada com sucesso. Bem-vindo ao SDC!'
        );
    }
}
