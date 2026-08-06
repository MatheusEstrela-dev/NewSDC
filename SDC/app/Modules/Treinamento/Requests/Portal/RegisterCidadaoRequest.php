<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests\Portal;

use App\Rules\CpfValido;
use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class RegisterCidadaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:cidadaos,email'],
            'cpf' => [
                'bail',
                'required',
                'string',
                'size:11',
                new CpfValido(),
                'unique:cidadaos,cpf',
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
            'cpf.unique' => 'Ja existe um usuario cadastrado com este CPF. Se voce e servidor, entre com seu login de servidor; caso contrario, use a opcao "Entrar" com a senha do seu cadastro.',
            'telefone.regex' => 'O telefone deve conter 11 numeros, com DDD (ex: 31912345678).',
            'aceite_lgpd.accepted' => 'E preciso aceitar os termos de uso e a politica de privacidade (LGPD) para se cadastrar.',
        ];
    }

    public function prepareForValidation(): void
    {
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
