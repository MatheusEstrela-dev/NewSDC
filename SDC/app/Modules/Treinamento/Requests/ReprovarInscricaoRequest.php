<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReprovarInscricaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('treinamento.inscricoes.reprovar') ?? false;
    }

    public function rules(): array
    {
        return [
            'observacoes' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'observacoes.required' => 'Informe o motivo da reprovacao.',
        ];
    }
}
