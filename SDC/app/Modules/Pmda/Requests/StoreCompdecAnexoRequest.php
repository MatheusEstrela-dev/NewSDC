<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Upload de documento (Leis e Decretos) do COMPDEC a partir da aba do PMDA.
 * Persiste como CompdecAnexo do orgao COMPDEC do municipio do plano.
 */
class StoreCompdecAnexoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pmda.planos.edit') ?? false;
    }

    public function rules(): array
    {
        return [
            'tipo'          => ['required', Rule::in(['lei', 'decreto', 'portaria', 'regimento', 'outros'])],
            'titulo'        => ['required', 'string', 'max:150'],
            'descricao'     => ['nullable', 'string', 'max:255'],
            'numero'        => ['nullable', 'string', 'max:50'],
            'data_emissao'  => ['nullable', 'date'],
            'data_validade' => ['nullable', 'date'],
            'arquivo'       => ['required', 'file', 'mimes:pdf,doc,docx,odt', 'max:10240'],
        ];
    }
}
