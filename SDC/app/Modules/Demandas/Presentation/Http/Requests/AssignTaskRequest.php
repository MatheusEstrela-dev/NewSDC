<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('demandas.assign');
    }

    public function rules(): array
    {
        return [
            'atribuido_para_id' => 'required|integer|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'atribuido_para_id.required' => 'O agente é obrigatório',
            'atribuido_para_id.exists' => 'Agente não encontrado',
        ];
    }
}
