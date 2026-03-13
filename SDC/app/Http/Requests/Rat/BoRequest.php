<?php

declare(strict_types=1);

namespace App\Http\Requests\Rat;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida dados de abertura de Boletim de Ocorrência (BO) no RAT.
 */
class BoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'numero_bos'           => ['nullable', 'string', 'max:50'],
            'historico'            => ['nullable', 'string'],
            'prazo_edicao'         => ['nullable', 'date'],
            'ocorrencia_origem_id' => ['nullable', 'integer', 'exists:rat_ocorrencias,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'ocorrencia_origem_id.exists' => 'Ocorrência de origem não encontrada.',
        ];
    }
}
