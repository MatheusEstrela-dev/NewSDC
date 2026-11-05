<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests;

use App\Modules\Treinamento\Models\Modulo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'modulo_id' => ['required', 'integer', Rule::exists(Modulo::class, 'id')],
        ];
    }
}
