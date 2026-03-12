<?php

declare(strict_types=1);

namespace App\Modules\Rat\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida os dados de criação de uma ocorrência RAT.
 */
class StoreRatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'numero_bos'           => ['nullable', 'string', 'max:50', 'unique:rat_ocorrencias,numero_bos'],
            'prazo_edicao'         => ['nullable', 'date'],
            'historico'            => ['nullable', 'string'],
            'ocorrencia_origem_id' => ['nullable', 'integer', 'exists:rat_ocorrencias,id'],
        ];
    }
}
