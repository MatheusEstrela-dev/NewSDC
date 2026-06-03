<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class FirstAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->must_change_password === true;
    }

    /**
     * Politica de senha mais rigida que a default do Laravel para o primeiro
     * acesso: forca mistura de caixa, numero e simbolo, alem do tamanho minimo.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.required' => 'A nova senha e obrigatoria.',
            'password.confirmed' => 'A confirmacao da senha nao coincide.',
        ];
    }
}
