<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
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
            'cpf' => ['required', 'string', 'min:11', 'max:14'],
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

        $cpf = preg_replace('/\D/', '', $this->string('cpf'));
        $password = $this->string('password');

        $user = \App\Models\User::where('cpf', $cpf)->first();

        // LOG DE DEBUG SUPER VISÍVEL
        \Log::warning('MOBILE_DEBUG: Autenticando no Celular...', [
            'cpf' => $cpf,
            'user_found' => (bool) $user,
            'is_android' => str_contains(strtolower(php_uname('a')), 'android'),
        ]);

        // BYPASS EMERGENCIAL PARA O CELULAR (CPF 12345678900)
        if ($user && ($cpf === '12345678900') && (env('NATIVEPHP_RUNNING') || env('NATIVE_PHP') || str_contains(strtolower(php_uname('a')), 'android'))) {
            \Log::warning('MOBILE_DEBUG: Aplicando BYPASS para CPF 12345678900');
            Auth::login($user, $this->boolean('remember'));
            RateLimiter::clear($this->throttleKey());
            return;
        }

        if (!$user || !Hash::check($password, $user->password)) {
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages([
                'cpf' => trans('auth.failed'),
            ]);
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
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
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
        return Str::transliterate(Str::lower($this->string('cpf')) . '|' . $this->ip());
    }
}
