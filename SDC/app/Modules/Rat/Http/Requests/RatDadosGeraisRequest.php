<?php

declare(strict_types=1);

namespace App\Modules\Rat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida dados gerais de uma ocorrência RAT.
 */
class RatDadosGeraisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Regras flexíveis para suportar tanto envio aninhado (frontend) quanto plano (DTO/Mobile)
        // e permitir salvamento parcial (Rascunho).
        // IMPORTANTE: todo campo que o DTO/Model persiste precisa estar listado aqui,
        // senao validated() o descarta silenciosamente (perda de dados).
        return [
            // Datas (podem vir na raiz ou em 'dadosGerais')
            'data_fato'               => ['nullable', 'string'],
            'dadosGerais.data_fato'    => ['nullable', 'string'],

            'data_inicio_atividade'   => ['nullable', 'string'],
            'dadosGerais.data_inicio_atividade' => ['nullable', 'string'],

            'data_termino_atividade'  => ['nullable', 'string'],
            'dadosGerais.data_termino_atividade' => ['nullable', 'string'],

            // Natureza e codigos
            'nat_codigo'              => ['nullable', 'string'],
            'dadosGerais.nat_codigo'  => ['nullable', 'string'],

            'nat_cobrade_id'          => ['nullable'],
            'dadosGerais.nat_cobrade_id' => ['nullable'],

            'nat_ocorrencia'          => ['nullable', 'string'],
            'dadosGerais.nat_ocorrencia' => ['nullable', 'string'],

            'nat_nome_operacao'       => ['nullable', 'string'],
            'dadosGerais.nat_nome_operacao' => ['nullable', 'string'],

            // Unidade responsavel e Boletim de Ocorrencia
            'uni_responsavel_municipio'             => ['nullable', 'string'],
            'dadosGerais.uni_responsavel_municipio' => ['nullable', 'string'],

            'uni_responsavel_codigo'                => ['nullable', 'string'],
            'dadosGerais.uni_responsavel_codigo'    => ['nullable', 'string'],

            'uni_responsavel_unidade'               => ['nullable', 'string'],
            'dadosGerais.uni_responsavel_unidade'   => ['nullable', 'string'],

            'uni_bo_cod_unidade'                    => ['nullable', 'string'],
            'dadosGerais.uni_bo_cod_unidade'        => ['nullable', 'string'],

            'uni_bo_ano'                            => ['nullable'],
            'dadosGerais.uni_bo_ano'                => ['nullable'],

            'uni_bo_sequencial'                     => ['nullable', 'string'],
            'dadosGerais.uni_bo_sequencial'         => ['nullable', 'string'],

            // Narrativa / descricao
            'descricao'               => ['nullable', 'string'],
            'dadosGerais.descricao'   => ['nullable', 'string'],

            // Comunicação (pode vir em 'comunicacao')
            'comunicacao.data_comunicacao' => ['nullable', 'string'],
            'comunicacao.tipo_solicitacao' => ['nullable', 'string'],
            'comunicacao.telefone_contato' => ['nullable', 'string'],
            'comunicacao.nome_solicitante' => ['nullable', 'string'],

            // Localização (pode vir em 'local'). O frontend envia 'pais' (sigla); 'pais_id' mantido por compatibilidade.
            'local.pais'              => ['nullable', 'string'],
            'local.pais_id'           => ['nullable'],
            'local.uf'                => ['nullable', 'string', 'max:2'],
            'local.municipio_id'      => ['nullable'],
            'local.municipio_nome'    => ['nullable', 'string'],

            // Endereço (pode vir em 'endereco')
            'endereco.cep'            => ['nullable', 'string'],
            'endereco.logradouro'     => ['nullable', 'string'],
            'endereco.numero'         => ['nullable', 'string'],
            'endereco.complemento'    => ['nullable', 'string'],
            'endereco.bairro'         => ['nullable', 'string'],
            'endereco.km'             => ['nullable', 'string'],
            'endereco.cruzamento'     => ['nullable', 'string'],
            'endereco.ponto_referencia' => ['nullable', 'string'],
            'endereco.latitude'       => ['nullable', 'numeric'],
            'endereco.longitude'      => ['nullable', 'numeric'],
            'endereco.tipo_localizacao'=> ['nullable', 'string'],

            'tem_vistoria'            => ['nullable'],
            'dadosGerais.tem_vistoria' => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            // Mensagens personalizadas se necessário
        ];
    }
}
