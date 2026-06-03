<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyEmailChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'size:6', 'regex:/^\d{6}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'O codigo deve ter exatamente 6 digitos numericos.',
            'code.size'  => 'O codigo deve ter exatamente 6 digitos.',
        ];
    }
}
