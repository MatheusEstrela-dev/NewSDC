<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (strlen($value) < 12) {
            $fail('A senha deve ter no minimo 12 caracteres.');
            return;
        }

        if (!preg_match('/[a-z]/', $value)) {
            $fail('A senha deve conter pelo menos uma letra minuscula.');
            return;
        }

        if (!preg_match('/[A-Z]/', $value)) {
            $fail('A senha deve conter pelo menos uma letra maiuscula.');
            return;
        }

        if (!preg_match('/[0-9]/', $value)) {
            $fail('A senha deve conter pelo menos um numero.');
            return;
        }

        if (!preg_match('/[@$!%*#?&]/', $value)) {
            $fail('A senha deve conter pelo menos um caractere especial (@$!%*#?&).');
            return;
        }

        if ($this->isCommonPassword($value)) {
            $fail('Esta senha e muito comum e nao e segura.');
            return;
        }
    }

    private function isCommonPassword(string $password): bool
    {
        $commonPasswords = [
            'password', '12345678', '123456789', 'qwerty123', 'abc123456',
            'password123', 'admin123', '123123123', '1q2w3e4r', 'senha123',
            'Senha@123', 'Admin@123', 'Root@123', 'User@123', 'Password@1',
        ];

        return in_array(strtolower($password), array_map('strtolower', $commonPasswords));
    }
}
