<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelarCertificadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('treinamento.certificados.cancelar') ?? false;
    }

    public function rules(): array
    {
        return [
            'motivo' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'motivo.required' => 'Informe o motivo do cancelamento.',
        ];
    }
}
