<?php

namespace App\Http\Controllers;

use App\Exceptions\Auth\EmailChange\EmailAlreadyInUseException;
use App\Exceptions\Auth\EmailChange\SameEmailException;
use App\Http\Requests\ProfileUpdateRequest;
use App\Services\Auth\EmailChangeService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): RedirectResponse
    {
        return Redirect::route('dashboard', ['open_profile' => 1]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(
        ProfileUpdateRequest $request,
        EmailChangeService $emailChangeService,
    ): RedirectResponse {
        $user = $request->user();
        $validated = $request->validated();

        // Troca de e-mail NUNCA vai direto pra users.email — passa
        // pelo fluxo de magic code (EmailChangeService).
        if (isset($validated['email']) && $validated['email'] !== $user->email) {
            $newEmail = $validated['email'];
            unset($validated['email']);

            try {
                $emailChangeService->requestChange($user, $newEmail, $request);
            } catch (EmailAlreadyInUseException) {
                return Redirect::back()->withErrors(['email' => 'E-mail ja cadastrado.']);
            } catch (SameEmailException) {
                // no-op silencioso (mesmo email digitado em duplicidade)
            }
        }

        $user->fill($validated)->save();

        Cache::forget("inertia_user_data_{$user->id}");

        return Redirect::back()->with('success', 'Perfil atualizado.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
