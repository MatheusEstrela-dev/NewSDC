<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTreinamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('treinamento.cursos.edit') ?? false;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['sometimes', 'required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
            'carga_horaria' => ['sometimes', 'required', 'integer', 'min:1'],
            'categoria' => ['sometimes', 'required', 'string', 'in:EVENTO,CURSO'],
            'tipo' => ['sometimes', 'required', 'string', 'in:PRESENCIAL,ONLINE'],
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
