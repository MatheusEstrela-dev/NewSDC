<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEscalaItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('plantao.escala.edit') ?? false;
    }

    public function rules(): array
    {
        return [
            'data' => ['required', 'date'],
            // Escalavel: o EXTRAORDINARIO existe para abrir turno na hora e nao
            // tem horario, entao nao pode ser planejado. O EscalaService repete
            // a checagem -- aqui e so para a mensagem chegar no campo certo.
            'tipo_turno_id' => [
                'required',
                'integer',
                Rule::exists('plantao_tipos_turno', 'id')
                    ->where('ativo', true)
                    ->where('escalavel', true),
            ],
            'plantonista_id' => [
                'required',
                'integer',
                Rule::exists('plantao_plantonistas', 'user_id')->where('ativo', true),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_turno_id.exists' => 'Tipo de turno invalido ou nao escalavel.',
            'plantonista_id.exists' => 'Selecione um plantonista ativo.',
        ];
    }
}
