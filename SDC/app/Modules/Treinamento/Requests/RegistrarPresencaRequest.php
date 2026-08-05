<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarPresencaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('treinamento.presencas.registrar') ?? false;
    }

    public function rules(): array
    {
        return [
            'qr_code_token' => ['required', 'uuid'],
            'modulo_id' => ['required', 'integer', 'exists:modulos,id'],
        ];
    }
}
