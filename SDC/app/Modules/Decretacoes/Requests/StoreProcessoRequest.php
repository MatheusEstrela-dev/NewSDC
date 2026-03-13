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
            'data_entrada'                      => 'required|date',
            'origem'                            => 'required|string|in:municipal,estadual',
            'municipio_id'                      => 'required|integer',
            'cobrade_id'                        => 'nullable|integer',
            'tipo_desastre_id'                  => 'nullable|integer',
            'situacao_anormalidade'             => 'nullable|string|in:N1,SE',
            'data_ocorrencia'                   => 'nullable|date',
            'data_vencimento_decreto'           => 'nullable|date',
            'status'                            => 'nullable|string|max:255',
            'analista_id'                       => 'nullable|string|max:255',
            'n_protocolo_fide'                  => 'nullable|string|max:50',
            'redec_id'                          => 'nullable|integer',
            'n_decreto_municipal'               => 'nullable|string|max:255',
            'data_decreto_municipal'            => 'nullable|date',
            'data_publicacao_decreto_municipal' => 'nullable|date',
            'prazo_vigencia_decreto'            => 'nullable|integer|min:1|max:365',
            'n_decreto_estadual'               => 'nullable|string|max:255',
            'data_decreto_estadual'             => 'nullable|date',
            'n_edicao_domg'                     => 'nullable|string|max:255',
            'data_publicacao_domg'              => 'nullable|date',
            'n_portaria_federal'                => 'nullable|string|max:255',
            'data_portaria_federal'             => 'nullable|date',
            'n_edicao_dou'                      => 'nullable|string|max:255',
            'data_publicacao_dou'               => 'nullable|date',
            'n_processo_sei'                    => 'nullable|string|max:255',
            'observacoes'                       => 'nullable|string',
            'informacoes_decreto'               => 'nullable|json',
        ];
    }
}
