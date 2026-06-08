<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'cpf' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $cpf = $this->input('cpf');
        $password = $this->input('password');

        // Busca unica por CPF, cacheada por 30s. Em burst de inicio de plantao
        // (todos logam juntos) o Postgres recebe 1 query por CPF a cada 30s em
        // vez de N. Cacheia o model inteiro de proposito: Auth::login precisa do
        // model completo para o restante do request (Inertia share etc.).
        $user = Cache::remember(
            "user:cpf:{$cpf}",
            30,
            fn () => User::where('cpf', $cpf)->first(),
        );

        if (!$user) {
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages([
                'cpf' => trans('auth.failed'),
            ]);
        }

        // 'pending' eh permitido propositalmente: representa o primeiro acesso com
        // senha provisoria. O EnsurePasswordChanged middleware obriga a troca em
        // /first-access antes de qualquer outra rota autenticada.
        if (!$user->active || in_array($user->status, ['inactive', 'suspended', 'blocked'], true)) {
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages([
                'cpf' => 'Seu usuário está desativado. Entre em contato com o suporte ou com o gestor do sistema.',
            ]);
        }

        // Verificacao direta do hash (sem segunda consulta por email que o
        // Auth::attempt fazia). Hash::check usa o driver atual (argon2id).
        if (!Hash::check($password, $user->password)) {
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages([
                'cpf' => trans('auth.failed'),
            ]);
        }

        // Rehash progressivo: usuarios com hash bcrypt antigo sao migrados para
        // argon2id no proximo login, sem quebrar quem ainda nao migrou.
        if (Hash::needsRehash($user->password)) {
            $user->forceFill(['password' => Hash::make($password)])->save();
            Cache::forget("user:cpf:{$cpf}");
        }

        Auth::login($user, $this->boolean('remember'));

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'cpf' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('cpf')).'|'.$this->ip());
    }
}
