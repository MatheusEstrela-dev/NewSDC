<?php

declare(strict_types=1);

namespace App\Http\Requests\Rat;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida os dados gerais de uma ocorrência RAT (tabela rat_relato_dados_gerais).
 */
class RatDadosGeraisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'data_fato'                  => ['nullable', 'date'],
            'data_inicio_atividade'      => ['nullable', 'date'],
            'data_termino_atividade'     => ['nullable', 'date', 'after_or_equal:data_inicio_atividade'],
            'nat_codigo'                 => ['nullable', 'string', 'max:191'],
            'nat_cobrade_id'             => ['nullable', 'integer'],
            'nat_ocorrencia'             => ['nullable', 'string', 'max:191'],
            'nat_nome_operacao'          => ['nullable', 'string', 'max:191'],
            'uni_responsavel_municipio'  => ['nullable', 'string', 'max:191'],
            'uni_responsavel_codigo'     => ['nullable', 'string', 'max:191'],
            'uni_responsavel_unidade'    => ['nullable', 'string', 'max:191'],
            'uni_bo_cod_unidade'         => ['nullable', 'string', 'max:191'],
            'uni_bo_ano'                 => ['nullable', 'integer', 'digits:4'],
            'uni_bo_sequencial'          => ['nullable', 'string', 'max:191'],
            'com_ocorrencia_data'        => ['nullable', 'date'],
            'com_ocorrencia_atendimento' => ['nullable', 'string'],
            'local_pais'                 => ['nullable', 'string', 'max:191'],
            'local_estadouf'             => ['nullable', 'string', 'size:2'],
            'local_municipio'            => ['nullable', 'string', 'max:191'],
            'local_cep'                  => ['nullable', 'string', 'max:9'],
            'local_logradoura_1'         => ['nullable', 'string', 'max:191'],
            'local_bairro'               => ['nullable', 'string', 'max:191'],
            'local_cruzamento'           => ['nullable', 'string', 'max:191'],
        ];
    }
}
