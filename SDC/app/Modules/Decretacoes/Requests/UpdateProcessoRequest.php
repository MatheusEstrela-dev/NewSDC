<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Requests;

use App\Modules\Decretacoes\Services\RedecService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProcessoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'data_entrada'                      => 'sometimes|date',
            'origem'                            => 'sometimes|string|in:municipal,estadual',
            'municipio_id'                      => 'sometimes|integer',
            'cobrade_id'                        => 'nullable|integer',
            'tipo_desastre_id'                  => 'nullable|integer',
            'situacao_anormalidade'             => 'nullable|string|in:N1,SE,ECP',
            'data_ocorrencia'                   => 'nullable|date',
            'analista_id'                       => 'nullable|string|max:255',
            'n_protocolo_fide'                  => 'nullable|string|max:50',
            // Lista derivada do catalogo `dec_redecs`: um `max:` fixo travou a
            // edicao nas 14 primeiras REDECs e rejeitava as regionais 15 a 19,
            // e a lista escrita em codigo obrigava deploy a cada regional nova.
            'redec_id'                          => RedecService::regrasDoCampo(),
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
