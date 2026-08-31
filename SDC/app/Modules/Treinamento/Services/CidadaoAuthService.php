<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Modules\Treinamento\DTOs\ResultadoAutenticacaoCidadao;
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
     * Tenta autenticar no guard "cidadao".
     *
     * A senha e conferida ANTES de qualquer outra checagem, com
     * guard()->validate() em vez de attempt(): validate() nao cria sessao, o que
     * permite recusar cadastro nao confirmado e conta inativa sem nunca
     * autenticar. Como esses dois desfechos so sao alcancados depois da senha
     * bater, eles nao revelam a existencia da conta para quem nao tem a
     * credencial - CREDENCIAL_INVALIDA continua cobrindo "CPF nao existe" e
     * "senha errada" com a mesma resposta.
     */
    public function attempt(string $cpf, string $password, bool $remember, string $ip): ResultadoAutenticacaoCidadao
    {
        if ($this->tooManyAttempts($cpf, $ip)) {
            return ResultadoAutenticacaoCidadao::credencialInvalida();
        }

        $cidadao = Cidadao::where('cpf', $cpf)->first();

        if (!$cidadao || !Auth::guard('cidadao')->validate(['cpf' => $cpf, 'password' => $password])) {
            RateLimiter::hit($this->throttleKey($cpf, $ip), 60);

            return ResultadoAutenticacaoCidadao::credencialInvalida();
        }

        // Senha correta: nao e tentativa de invasao, entao o contador zera mesmo
        // nos desfechos que nao autenticam. Nao ha o que forcar bruta aqui - quem
        // chegou neste ponto ja tem a credencial.
        RateLimiter::clear($this->throttleKey($cpf, $ip));

        if (!$cidadao->emailVerificado()) {
            return ResultadoAutenticacaoCidadao::emailNaoVerificado($cidadao);
        }

        if (!$cidadao->ativo) {
            return ResultadoAutenticacaoCidadao::contaInativa();
        }

        Auth::guard('cidadao')->login($cidadao, $remember);

        $cidadao->forceFill(['last_login_at' => now()])->saveQuietly();

        return ResultadoAutenticacaoCidadao::autenticado($cidadao);
    }

    private function throttleKey(string $cpf, string $ip): string
    {
        return Str::transliterate("cidadao-login:{$cpf}|{$ip}");
    }
}
