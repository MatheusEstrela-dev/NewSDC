<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTreinamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
            'carga_horaria' => ['required', 'integer', 'min:1'],
            'tipo' => ['required', 'in:PRESENCIAL,EAD,HIBRIDO'],
            'instrutor' => ['nullable', 'string', 'max:255'],
            'local' => ['nullable', 'string', 'max:255'],
            'data_inicio' => ['nullable', 'date', 'after_or_equal:today'],
            'data_fim' => ['nullable', 'date', 'after:data_inicio'],
            'numero_vagas' => ['nullable', 'integer', 'min:1'],
            'percentual_frequencia_minimo' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'O título é obrigatório.',
            'carga_horaria.required' => 'A carga horária é obrigatória.',
            'carga_horaria.min' => 'A carga horária deve ser maior que zero.',
            'tipo.required' => 'O tipo de treinamento é obrigatório.',
            'tipo.in' => 'Tipo de treinamento inválido.',
            'data_inicio.after_or_equal' => 'A data de início deve ser hoje ou uma data futura.',
            'data_fim.after' => 'A data de término deve ser posterior à data de início.',
        ];
    }
}
