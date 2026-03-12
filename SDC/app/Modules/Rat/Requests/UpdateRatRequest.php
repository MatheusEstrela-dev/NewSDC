<?php

declare(strict_types=1);

namespace App\Modules\Rat\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida os dados de atualização de uma ocorrência RAT.
 */
class UpdateRatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id') ?? $this->route('rat');

        return [
            'numero_bos'           => ['nullable', 'string', 'max:50', 'unique:rat_ocorrencias,numero_bos,' . $id],
            'prazo_edicao'         => ['nullable', 'date'],
            'historico'            => ['nullable', 'string'],
            'ocorrencia_origem_id' => ['nullable', 'integer', 'exists:rat_ocorrencias,id'],
        ];
    }
}
