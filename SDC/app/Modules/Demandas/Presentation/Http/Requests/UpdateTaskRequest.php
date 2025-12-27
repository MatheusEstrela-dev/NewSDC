<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('demandas.edit');
    }

    public function rules(): array
    {
        return [
            'titulo' => 'sometimes|required|string|max:255',
            'descricao' => 'nullable|string',
            'categoria' => 'nullable|string|max:100',
            'subcategoria' => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'O título é obrigatório',
            'titulo.max' => 'O título não pode ter mais de 255 caracteres',
        ];
    }
}
