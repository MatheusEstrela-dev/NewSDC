<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Providers\RouteServiceProvider;
use App\Services\Auth\OnboardingService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class NewPasswordController extends Controller
{
    /**
     * Chave da session que guarda o contexto (token+email) ate o submit do form.
     * Mantemos esses valores SO no servidor — nao expor no data-page do Inertia.
     */
    private const SESSION_KEY = 'password_reset_context';

    public function __construct(
        private readonly OnboardingService $onboarding,
    ) {
    }

    /**
     * GET /reset-password/{token}
     *
     * Decripta o payload da URL (Crypt::encryptString de JSON com token+email
     * gerado em AppServiceProvider::boot), guarda na session do navegador e
     * renderiza a tela. **Nao envia token nem email para o frontend** —
     * impede que view-source / devtools exponham o token de reset.
     *
     * Compat: se o param vier no formato antigo (token plano + ?email=),
     * tambem aceita.
     */
    public function create(Request $request): Response
    {
        [$token, $email] = $this->extractContext($request);

        $hasContext = $token !== '' && $email !== '';

        if ($hasContext) {
            $request->session()->put(self::SESSION_KEY, [
                'token'     => $token,
                'email'     => $email,
                'expires_at'=> Carbon::now()->addMinutes(15)->timestamp,
            ]);
        }

        return Inertia::render('Auth/NewPassword', [
            'hasResetContext' => $hasContext,
        ]);
    }

    /**
     * POST /reset-password
     *
     * Le token+email da session (nao do request). Form so envia password +
     * confirmacao. Se o contexto nao existir / expirou, redireciona pra
     * solicitar novo link.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $context = $request->session()->get(self::SESSION_KEY);

        if (!$this->contextIsValid($context)) {
            $request->session()->forget(self::SESSION_KEY);

            return redirect()
                ->route('password.request')
                ->withErrors(['email' => 'Link de redefinicao invalido ou expirado. Solicite um novo.']);
        }

        $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $resetUser = null;
        $completedFirstAccess = false;

        $status = Password::reset(
            [
                'token'                 => $context['token'],
                'email'                 => $context['email'],
                'password'              => $request->password,
                'password_confirmation' => $request->password_confirmation,
            ],
            function ($user) use ($request, &$resetUser, &$completedFirstAccess) {
                $resetUser = $user;
                $completedFirstAccess = (bool) $user->must_change_password;

                if ($completedFirstAccess) {
                    $this->onboarding->completeFirstAccess($user, $request->string('password')->value());
                } else {
                    $user->forceFill([
                        'password'       => Hash::make($request->password),
                        'remember_token' => Str::random(60),
                    ])->save();
                }

                event(new PasswordReset($user));
            }
        );

        $request->session()->forget(self::SESSION_KEY);

        if ($status === Password::PASSWORD_RESET) {
            if ($completedFirstAccess && $resetUser !== null) {
                Auth::login($resetUser);
                $request->session()->regenerate();

                $resetUser->recordLogin(
                    $request->attributes->get('client_ip') ?? $request->ip(),
                    $request->userAgent(),
                );

                Cache::forget("inertia_user_data_{$resetUser->id}");
                AuditLog::logLogin($resetUser->id);
                $request->session()->flash('show_welcome_tour', true);

                return redirect(RouteServiceProvider::HOME)->with(
                    'success',
                    'Senha atualizada com sucesso. Bem-vindo ao SDC!'
                );
            }

            return redirect()->route('login')->with('success', __($status));
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }

    /**
     * Extrai {token, email} do parametro de rota — encriptado ou plano (compat).
     *
     * @return array{0:string,1:string}
     */
    private function extractContext(Request $request): array
    {
        $raw   = (string) $request->route('token');
        $token = $raw;
        $email = (string) $request->query('email', '');

        try {
            $payload = json_decode(Crypt::decryptString($raw), true, 16, JSON_THROW_ON_ERROR);
            if (is_array($payload) && isset($payload['token'])) {
                $token = (string) $payload['token'];
                $email = (string) ($payload['email'] ?? $email);
            }
        } catch (DecryptException|\JsonException) {
            // Formato antigo (plano): mantem $token = $raw, $email = ?email=
        }

        return [$token, $email];
    }

    private function contextIsValid(mixed $context): bool
    {
        return is_array($context)
            && !empty($context['token'])
            && !empty($context['email'])
            && isset($context['expires_at'])
            && (int) $context['expires_at'] > Carbon::now()->timestamp;
    }
}
