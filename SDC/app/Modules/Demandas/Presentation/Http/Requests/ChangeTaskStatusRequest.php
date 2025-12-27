<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangeTaskStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('demandas.change-status');
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:aberta,em_andamento,aguardando,resolvida,fechada,cancelada',
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'O status é obrigatório',
            'status.in' => 'Status inválido',
        ];
    }
}
