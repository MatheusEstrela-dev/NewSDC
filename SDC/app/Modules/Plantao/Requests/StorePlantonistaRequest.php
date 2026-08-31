<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlantonistaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('plantao.plantonistas.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
                // A tabela tem unique em user_id: barrar aqui devolve mensagem
                // de campo em vez de erro 500 de constraint.
                Rule::unique('plantao_plantonistas', 'user_id'),
            ],
            'posto' => ['nullable', 'string', 'max:20'],
            'observacao' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.unique' => 'Este usuario ja esta cadastrado como plantonista.',
        ];
    }
}
