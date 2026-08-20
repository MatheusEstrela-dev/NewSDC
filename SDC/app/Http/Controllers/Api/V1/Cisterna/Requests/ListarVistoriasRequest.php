<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Cisterna\Requests;

use App\Modules\Cisterna\Enums\EtapaVistoria;
use Illuminate\Validation\Rule;

class ListarVistoriasRequest extends FiltroApiRequest
{
    /**
     * @return array<string, mixed>
     */
    protected function regrasDoFiltro(): array
    {
        return [
            'etapa' => ['sometimes', Rule::in(EtapaVistoria::valores())],
            'beneficiario_id' => ['sometimes', 'integer', 'exists:cisterna_beneficiarios,id'],
            'municipio_id' => ['sometimes', 'integer', 'exists:municipios,id'],
            'comunidade_id' => ['sometimes', 'integer', 'exists:cisterna_comunidades,id'],
            'numero_instalacao' => ['sometimes', 'integer', 'min:1'],
            'concluida' => ['sometimes', 'boolean'],
            'data_relatorio_inicio' => ['sometimes', 'date'],
            'data_relatorio_fim' => ['sometimes', 'date', 'after_or_equal:data_relatorio_inicio'],
        ];
    }
}
