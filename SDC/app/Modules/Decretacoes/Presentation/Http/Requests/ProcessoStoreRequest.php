<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request: Validação para criação de Processo
 * Seguindo papiro.md - Camada de VALIDAÇÃO
 */
class ProcessoStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // TODO: Implementar autorização por permissão
    }

    public function rules(): array
    {
        return [
            // Identificação do Processo
            'tipo_desastre_id' => 'required|integer',
            'cobrade_id' => 'required|integer',
            'origem' => 'required|in:municipal,estadual',
            'municipio_id' => 'required|integer',
            'redec_id' => 'required|integer',
            'situacao_anormalidade' => 'required|in:N1,SE',

            // Datas e Prazos
            'data_entrada' => 'required|date',
            'data_ocorrencia' => 'required|date|before_or_equal:data_entrada',
            'data_vencimento_decreto' => 'required|date|after:data_entrada',

            // Status
            'status' => 'required|string',
            'analista_id' => 'nullable|integer',

            // Decreto Municipal
            'n_decreto_municipal' => 'nullable|string|max:100',
            'data_decreto_municipal' => 'nullable|date',
            'data_publicacao_decreto_municipal' => 'nullable|date',
            'prazo_vigencia_decreto' => 'nullable|integer|min:1',

            // Reconhecimento Estadual
            'n_decreto_estadual' => 'nullable|string|max:100',
            'data_decreto_estadual' => 'nullable|date',
            'n_edicao_domg' => 'nullable|string|max:50',
            'data_publicacao_domg' => 'nullable|date',

            // Reconhecimento Federal
            'n_portaria_federal' => 'nullable|string|max:100',
            'data_portaria_federal' => 'nullable|date',
            'n_edicao_dou' => 'nullable|string|max:50',
            'data_publicacao_dou' => 'nullable|date',

            // Informações Adicionais
            'n_processo_sei' => 'required|string|max:50',
            'observacoes' => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_desastre_id.required' => 'O tipo de desastre é obrigatório.',
            'cobrade_id.required' => 'O COBRADE é obrigatório.',
            'origem.required' => 'A origem é obrigatória.',
            'origem.in' => 'A origem deve ser municipal ou estadual.',
            'municipio_id.required' => 'O município é obrigatório.',
            'redec_id.required' => 'A REDEC é obrigatória.',
            'situacao_anormalidade.required' => 'A situação de anormalidade é obrigatória.',
            'situacao_anormalidade.in' => 'A situação deve ser N1 ou SE.',
            'data_entrada.required' => 'A data de entrada é obrigatória.',
            'data_ocorrencia.required' => 'A data de ocorrência é obrigatória.',
            'data_ocorrencia.before_or_equal' => 'A data de ocorrência deve ser anterior ou igual à data de entrada.',
            'data_vencimento_decreto.required' => 'A data de vencimento é obrigatória.',
            'data_vencimento_decreto.after' => 'A data de vencimento deve ser posterior à data de entrada.',
            'n_processo_sei.required' => 'O número do processo SEI é obrigatório.',
        ];
    }
}
