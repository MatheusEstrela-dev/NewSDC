<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests\Portal;

use App\Rules\CpfValido;
use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RegisterCidadaoRequest extends FormRequest
{
    /**
     * Limite que o cidadao legitimo enxerga. Fica ABAIXO do teto de abuso da
     * rota ('portal-registro', 20/min) de proposito: assim quem encosta primeiro
     * e neste, que devolve mensagem inline no formulario, e a 429 crua sobra so
     * pra automacao. Mesmo raciocinio do login web (ver routes/auth.php).
     */
    private const MAX_TENTATIVAS = 8;
    private const JANELA_SEGUNDOS = 60;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Roda antes das regras, e conta TODA tentativa - nao so as que dao certo.
     * Alem de barrar cadastro automatizado, e o que limita a sondagem dos
     * unique(): as regras de cpf/e-mail consultam a tabela `users`, entao sem
     * teto qualquer um descobriria, um CPF por vez, quais pertencem a servidor.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function garantirDentroDoLimite(): void
    {
        if (RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_TENTATIVAS)) {
            $segundos = RateLimiter::availableIn($this->throttleKey());

            throw ValidationException::withMessages([
                'cpf' => "Muitas tentativas de cadastro. Tente novamente em {$segundos} segundos.",
                // O front ja anima a contagem regressiva a partir desta chave
                // (mesmo contrato do LoginRequest::throwThrottle).
                'retry_after' => (string) $segundos,
            ]);
        }

        RateLimiter::hit($this->throttleKey(), self::JANELA_SEGUNDOS);
    }

    private function throttleKey(): string
    {
        return 'portal-registro:' . $this->ip();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'bail',
                'required',
                'string',
                'email',
                'max:255',
                // whereNotNull: cadastro que nunca confirmou o e-mail NAO reserva
                // o endereco. Ele e descartado no RegisterController - senao
                // bastaria cadastrar o e-mail de alguem para tranca-lo fora do
                // portal para sempre.
                Rule::unique('cidadaos', 'email')->whereNotNull('email_verified_at'),
                // Mesmo motivo do CPF logo abaixo, mas por outro caminho: o
                // NewPasswordController escolhe o broker de reset por
                // "!User::where('email')->exists()". Um cidadao com e-mail de
                // servidor teria o token gravado em cidadao_password_reset_tokens
                // e validado contra password_reset_tokens - nunca bate, e a conta
                // fica permanentemente sem "esqueci minha senha".
                Rule::unique('users', 'email'),
            ],
            'cpf' => [
                'bail',
                'required',
                'string',
                'size:11',
                new CpfValido(),
                // Mesma regra do e-mail acima: so cadastro confirmado reserva CPF.
                Rule::unique('cidadaos', 'cpf')->whereNotNull('email_verified_at'),
                // O login unificado resolve primeiro por "users": um cidadao
                // cadastrado com CPF de servidor ja existente nunca conseguiria
                // entrar por essa conta, sem nenhum erro visivel no cadastro.
                Rule::unique('users', 'cpf'),
            ],
            'telefone' => ['bail', 'nullable', 'string', 'regex:/^\d{11}$/'],
            'password' => ['required', 'confirmed', new StrongPassword()],
            'aceite_lgpd' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'cpf.size' => 'O CPF deve conter 11 numeros.',
            'email.unique' => 'Ja existe um usuario cadastrado com este e-mail. Se voce e servidor, entre com seu login de servidor; caso contrario, use a opcao "Entrar" com a senha do seu cadastro.',
            'cpf.unique' => 'Ja existe um usuario cadastrado com este CPF. Se voce e servidor, entre com seu login de servidor; caso contrario, use a opcao "Entrar" com a senha do seu cadastro.',
            'telefone.regex' => 'O telefone deve conter 11 numeros, com DDD (ex: 31912345678).',
            'aceite_lgpd.accepted' => 'E preciso aceitar os termos de uso e a politica de privacidade (LGPD) para se cadastrar.',
        ];
    }

    public function prepareForValidation(): void
    {
        $this->garantirDentroDoLimite();

        $this->merge([
            'cpf' => preg_replace('/\D/', '', (string) $this->input('cpf')),
            // String vazia nao e null: sem isso, "nullable" nao pula o regex
            // abaixo e o campo opcional passaria a bloquear o cadastro.
            'telefone' => $this->filled('telefone')
                ? preg_replace('/\D/', '', (string) $this->input('telefone'))
                : null,
        ]);
    }
}
