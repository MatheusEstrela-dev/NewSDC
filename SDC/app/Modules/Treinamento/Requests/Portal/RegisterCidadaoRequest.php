<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests\Portal;

use App\Rules\CpfValido;
use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;
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
            'cpf' => ['required', 'string', 'size:11', new CpfValido(), 'unique:cidadaos,cpf'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', new StrongPassword()],
            'aceite_lgpd' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'aceite_lgpd.accepted' => 'E preciso aceitar os termos de uso e a politica de privacidade (LGPD) para se cadastrar.',
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'cpf' => preg_replace('/\D/', '', (string) $this->input('cpf')),
        ]);
    }
}
