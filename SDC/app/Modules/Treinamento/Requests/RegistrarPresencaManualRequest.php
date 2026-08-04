<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarPresencaManualRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('treinamento.presencas.registrar') ?? false;
    }

    public function rules(): array
    {
        return [
            'inscricao_id' => ['required', 'integer', 'exists:inscricoes,id'],
            'modulo_id' => ['required', 'integer', 'exists:modulos,id'],
        ];
    }
}
