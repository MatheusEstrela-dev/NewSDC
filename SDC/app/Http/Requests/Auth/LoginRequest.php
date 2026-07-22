<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Models\UserStatusHistory;
use App\Support\Security\PasswordVerifier;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Escalonamento de bloqueio por CPF (conta):
     *   rodada 0: 5 tentativas -> espera 60s
     *   rodada 1: 3 tentativas -> espera 180s
     *   rodada 2: 1 tentativa  -> BLOQUEIA a conta no banco (status='blocked')
     */
    private const STAGE_ATTEMPTS = [5, 3, 1];
    private const STAGE_WAITS = [60, 180]; // s; apos a ultima rodada -> bloqueio
    private const COUNTER_TTL_HOURS = 24;
    /** Hash argon2id descartavel p/ tempo constante quando o CPF nao existe. */
    private const DUMMY_HASH = '$argon2id$v=19$m=65536,t=2,p=2$V0NSck1LZ1BGcWZ5dWdrTw$UVr0RwwqX5IPvBo3zG1dSAYrKjYHFSHD97OYirBorUI';

    public function authorize(): bool
    {
        return true;
    }

    /**
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
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $cpf = $this->input('cpf');
        $password = $this->input('password');

        $user = Cache::remember(
            "user:cpf:{$cpf}",
            30,
            fn () => User::where('cpf', $cpf)->first(),
        );

        if (!$user) {
            // Tempo constante: mesmo custo de um verify real p/ nao vazar a
            // existencia do CPF por diferenca de tempo de resposta.
            app(PasswordVerifier::class)->verify($password, self::DUMMY_HASH);
            $this->registerFailedAttempt(null);
            throw ValidationException::withMessages(['cpf' => trans('auth.failed')]);
        }

        if (!$user->active || in_array($user->status, ['inactive', 'suspended', 'blocked'], true)) {
            throw ValidationException::withMessages([
                'cpf' => 'Seu usuário está desativado ou bloqueado. Entre em contato com o suporte ou com o gestor do sistema.',
            ]);
        }

        if (!app(PasswordVerifier::class)->verify($password, $user->password)) {
            event(new Failed('web', $user, ['email' => $user->email]));
            $this->registerFailedAttempt($user); // pode lancar throttle/bloqueio
            throw ValidationException::withMessages(['cpf' => trans('auth.failed')]);
        }

        // Rehash progressivo bcrypt -> argon2id no login.
        if (Hash::needsRehash($user->password)) {
            $user->forceFill(['password' => Hash::make($password)])->save();
            Cache::forget("user:cpf:{$cpf}");
        }

        Auth::login($user, $this->boolean('remember'));

        $this->clearThrottleState();
    }

    /**
     * Bloqueia se houver penalidade (espera) ativa para o CPF.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $until = Cache::get($this->penaltyKey());
        if (!$until) {
            return;
        }
        $remaining = (int) ceil(now()->diffInSeconds($until, false));
        if ($remaining > 0) {
            event(new Lockout($this));
            $this->throwThrottle($remaining);
        }
        Cache::forget($this->penaltyKey()); // expirou: libera a proxima rodada
    }

    /**
     * Contabiliza a falha e aplica o escalonamento. Ao atingir o limite da
     * rodada: espera (rodadas 0/1) ou bloqueio da conta no banco (rodada final).
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    private function registerFailedAttempt(?User $user): void
    {
        $stage = (int) Cache::get($this->stageKey(), 0);
        $attempts = ((int) Cache::get($this->attemptsKey(), 0)) + 1;
        Cache::put($this->attemptsKey(), $attempts, now()->addHours(self::COUNTER_TTL_HOURS));

        $threshold = self::STAGE_ATTEMPTS[$stage] ?? 1;
        if ($attempts < $threshold) {
            return; // ainda dentro da rodada; segue o erro normal de credenciais
        }

        // Rodadas com espera (0 e 1)
        if ($stage < count(self::STAGE_WAITS)) {
            $wait = self::STAGE_WAITS[$stage];
            Cache::put($this->penaltyKey(), now()->addSeconds($wait), now()->addSeconds($wait));
            Cache::put($this->stageKey(), $stage + 1, now()->addHours(self::COUNTER_TTL_HOURS));
            Cache::put($this->attemptsKey(), 0, now()->addHours(self::COUNTER_TTL_HOURS));
            event(new Lockout($this));
            Log::warning('security.login.lockout', [
                'cpf' => $this->maskedCpf(), 'ip' => $this->ip(),
                'stage' => $stage + 1, 'wait_seconds' => $wait,
            ]);
            $this->throwThrottle($wait);
        }

        // Rodada final: bloqueia a CONTA no banco.
        $this->clearThrottleState();
        if ($user) {
            $anterior = $user->status;
            $user->forceFill(['status' => 'blocked'])->save();
            Cache::forget("user:cpf:{$user->cpf}");
            UserStatusHistory::logStatusChange(
                $user, $anterior, 'blocked',
                'Bloqueio automatico por excesso de tentativas de login.'
            );
            Log::warning('security.login.account_blocked', [
                'cpf' => $this->maskedCpf(), 'ip' => $this->ip(), 'user_id' => $user->id,
            ]);
            throw ValidationException::withMessages([
                'cpf' => 'Sua conta foi bloqueada por segurança após múltiplas tentativas. Contate o suporte ou o gestor do sistema.',
            ]);
        }

        // CPF inexistente: sem conta para bloquear -> mantem espera longa.
        $waits = self::STAGE_WAITS;
        $wait = end($waits) ?: 180;
        Cache::put($this->penaltyKey(), now()->addSeconds($wait), now()->addSeconds($wait));
        $this->throwThrottle($wait);
    }

    private function throwThrottle(int $seconds): void
    {
        throw ValidationException::withMessages([
            'cpf' => trans('auth.throttle', ['seconds' => $seconds, 'minutes' => ceil($seconds / 60)]),
            'retry_after' => (string) $seconds, // front anima a contagem regressiva
        ]);
    }

    private function clearThrottleState(): void
    {
        Cache::forget($this->attemptsKey());
        Cache::forget($this->stageKey());
        Cache::forget($this->penaltyKey());
    }

    private function cpfDigits(): string
    {
        return preg_replace('/\D/', '', (string) $this->input('cpf'));
    }

    private function maskedCpf(): string
    {
        $cpf = $this->cpfDigits();
        return strlen($cpf) >= 11 ? substr($cpf, 0, 3) . '******' . substr($cpf, -2) : '***';
    }

    private function attemptsKey(): string
    {
        return 'login:att:' . $this->cpfDigits();
    }

    private function stageKey(): string
    {
        return 'login:stage:' . $this->cpfDigits();
    }

    private function penaltyKey(): string
    {
        return 'login:penalty:' . $this->cpfDigits();
    }
}
