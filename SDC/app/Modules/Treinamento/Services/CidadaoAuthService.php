<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Modules\Treinamento\Models\Cidadao;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Autenticacao do guard "cidadao", extraida em service para ser reaproveitada
 * tanto pela tela de login unificada (App\Http\Controllers\Auth) quanto por
 * qualquer outro ponto de entrada do Portal de Treinamentos.
 */
class CidadaoAuthService
{
    public function tooManyAttempts(string $cpf, string $ip): bool
    {
        return RateLimiter::tooManyAttempts($this->throttleKey($cpf, $ip), 5);
    }

    public function availableIn(string $cpf, string $ip): int
    {
        return RateLimiter::availableIn($this->throttleKey($cpf, $ip));
    }

    /**
     * Tenta autenticar no guard "cidadao". Retorna false tanto para "nao
     * encontrado" quanto para "senha errada" (nao vaza qual dos dois casos
     * ocorreu). Nao lanca excecao - quem chama decide a mensagem/campo do erro.
     */
    public function attempt(string $cpf, string $password, bool $remember, string $ip): bool
    {
        if ($this->tooManyAttempts($cpf, $ip)) {
            return false;
        }

        $cidadao = Cidadao::where('cpf', $cpf)->first();

        if (!$cidadao || !$cidadao->ativo || !Auth::guard('cidadao')->attempt(
            ['cpf' => $cpf, 'password' => $password],
            $remember
        )) {
            RateLimiter::hit($this->throttleKey($cpf, $ip), 60);
            return false;
        }

        RateLimiter::clear($this->throttleKey($cpf, $ip));
        $cidadao->forceFill(['last_login_at' => now()])->saveQuietly();

        return true;
    }

    private function throttleKey(string $cpf, string $ip): string
    {
        return Str::transliterate("cidadao-login:{$cpf}|{$ip}");
    }
}
