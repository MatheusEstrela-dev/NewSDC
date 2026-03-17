<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceiveProcessoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'data_entrada'           => 'required|date',
            'origem'                 => 'required|string|in:municipal,estadual',
            'municipio_id'           => 'required|integer',
            'cobrade_id'             => 'nullable|integer',
            'tipo_desastre_id'       => 'nullable|integer',
            'situacao_anormalidade'  => 'nullable|string|in:N1,SE',
            'data_ocorrencia'        => 'nullable|date',
            'analista_id'            => 'nullable|string|max:255',
            'n_protocolo_fide'       => 'nullable|string|max:50',
            'redec_id'               => 'nullable|integer',
            'n_decreto_municipal'    => 'nullable|string|max:255',
            'data_decreto_municipal' => 'nullable|date',
            'observacoes'            => 'nullable|string',
        ];
    }
}
