<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProcessoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'data_entrada' => 'required|date',
            'processo' => 'required|string|in:MUNICIPAL,ESTADUAL',
            'municipios' => 'required|array|min:1',
            'municipios.*' => 'integer|exists:cedec_municipio,id',
            'tipo_desastre_id' => 'nullable|integer',
            'tipo_desastre' => 'nullable|string|in:SE,ECP',
            'data_ocorrencia_desastre' => 'nullable|date',
            'n_protocolo_fide' => 'nullable|string|max:100',
            'decreto_municipal' => 'nullable|string|max:255',
            'data_decreto_municipal' => 'nullable|date',
            'data_publicacao_mg' => 'nullable|date',
            'prazo_vigencia' => 'nullable|integer|min:1|max:365',
            'analista' => 'nullable|string|max:255',
            'observacoes' => 'nullable|string',
            'informacoes_decreto' => 'nullable|json',
        ];
    }
}
