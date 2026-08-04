<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AprovarInscricaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('treinamento.inscricoes.aprovar') ?? false;
    }

    public function rules(): array
    {
        return [
            'observacoes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
