<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests\Gestao;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Edicao de turma pela Area do Gestor. Todo campo e opcional (so o que vier
 * e alterado); autorizacao fica na rota, nao aqui.
 */
class TurmaUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['sometimes', 'required', 'string', 'max:120'],
            'data_inicio' => ['sometimes', 'required', 'date'],
            'data_fim' => ['sometimes', 'required', 'date', 'after_or_equal:data_inicio'],
            'numero_vagas' => ['sometimes', 'required', 'integer', 'min:1'],
            'local' => ['nullable', 'string', 'max:255'],
            'municipio_id' => ['nullable', 'integer', Rule::exists('municipios', 'id')->where('uf', 'MG')],
        ];
    }
}
