<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTreinamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('treinamento.cursos.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
            'carga_horaria' => ['required', 'integer', 'min:1'],
            'categoria' => ['required', 'string', 'in:EVENTO,CURSO'],
            'tipo' => ['required', 'string', 'in:PRESENCIAL,ONLINE'],
            'instrutor' => ['nullable', 'string', 'max:255'],
            'local' => ['nullable', 'string', 'max:255'],
            'data_inicio' => ['nullable', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'hora_inicio' => ['nullable', 'date_format:H:i'],
            'numero_vagas' => ['nullable', 'integer', 'min:1'],
            'percentual_frequencia_minimo' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'presenca_autoconfirmavel' => ['nullable', 'boolean'],
        ];
    }
}
