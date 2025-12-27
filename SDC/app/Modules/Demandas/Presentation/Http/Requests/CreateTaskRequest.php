<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo' => 'required|in:incidente,solicitacao',
            'titulo' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'categoria' => 'nullable|string|max:100',
            'subcategoria' => 'nullable|string|max:100',
            'urgencia' => 'nullable|in:alta,media,baixa',
            'impacto' => 'nullable|in:alto,medio,baixo',
        ];
    }

    public function messages(): array
    {
        return [
            'tipo.required' => 'O tipo é obrigatório',
            'tipo.in' => 'Tipo inválido',
            'titulo.required' => 'O título é obrigatório',
            'titulo.max' => 'O título não pode ter mais de 255 caracteres',
            'urgencia.in' => 'Urgência inválida',
            'impacto.in' => 'Impacto inválido',
        ];
    }
}
