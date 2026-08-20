<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Cisterna\Requests;

use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Enums\SituacaoAnalise;
use App\Modules\Cisterna\Enums\SituacaoObra;
use App\Modules\Cisterna\Services\BeneficiarioService;
use Illuminate\Validation\Rule;

class ListarBeneficiariosRequest extends FiltroApiRequest
{
    /**
     * @return array<int, string>
     */
    protected function camposMultivalor(): array
    {
        return ['comunidade_id', 'situacao_analise', 'situacao_obra', 'ordem_servico_id'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function regrasDoFiltro(): array
    {
        return [
            'municipio_id' => ['sometimes', 'integer', 'exists:municipios,id'],
            'comunidade_id' => ['sometimes', 'array'],
            'comunidade_id.*' => ['integer', 'exists:cisterna_comunidades,id'],
            'situacao_analise' => ['sometimes', 'array'],
            'situacao_analise.*' => [Rule::in(SituacaoAnalise::valores())],
            'situacao_obra' => ['sometimes', 'array'],
            'situacao_obra.*' => [Rule::in(SituacaoObra::valores())],
            'ordem_servico_id' => ['sometimes', 'array'],
            'ordem_servico_id.*' => ['integer', 'exists:cisterna_ordens_servico,id'],
            'lote_id' => ['sometimes', 'integer', 'exists:cisterna_lotes,id'],
            'cpf' => ['sometimes', 'string', 'max:14'],
            'search' => ['sometimes', 'string', 'max:150'],
            'data_inicio' => ['sometimes', 'date'],
            'data_fim' => ['sometimes', 'date', 'after_or_equal:data_inicio'],
            'atendido_por_pipa' => ['sometimes', 'boolean'],
            'numero_instalacao' => ['sometimes', 'integer', 'min:1'],
            'etapa_concluida' => ['sometimes', Rule::in(EtapaVistoria::valores())],
            'etapa_pendente' => ['sometimes', Rule::in(EtapaVistoria::valores())],
            'ranqueamento' => ['sometimes', 'boolean'],
            // A whitelist e a mesma que o service aceita: prometer ordenacao
            // que o ORDER BY ignora e pior que nao prometer.
            'sort' => ['sometimes', Rule::in(BeneficiarioService::colunasOrdenaveis())],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
        ];
    }
}
