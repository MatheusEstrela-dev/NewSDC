<?php

declare(strict_types=1);

namespace App\Models\Rat\Relatos;

/**
 * Dados gerais de uma ocorrência RAT.
 *
 * Campos-chave (ver migração 2026_02_10_133300):
 * @property int             $id
 * @property \Carbon\Carbon|null $data_fato
 * @property string|null     $nat_codigo         Código/rat_codigo da ocorrência
 * @property int|null        $nat_cobrade_id      COBRADE
 * @property string|null     $nat_nome_operacao
 * @property string|null     $local_municipio
 * @property string|null     $local_estadouf
 */
class RatRelatoDadosGerais extends RatRelato
{
    protected $table = 'rat_relato_dados_gerais';

    protected $fillable = [
        'created_by',
        'data_fato',
        'data_inicio_atividade',
        'data_termino_atividade',
        'nat_codigo',
        'nat_cobrade_id',
        'nat_ocorrencia',
        'nat_nome_operacao',
        'uni_responsavel_municipio',
        'uni_responsavel_codigo',
        'uni_responsavel_unidade',
        'uni_bo_cod_unidade',
        'uni_bo_ano',
        'uni_bo_sequencial',
        'com_ocorrencia_data',
        'com_ocorrencia_atendimento',
        'local_pais',
        'local_cruzamento',
        'local_estadouf',
        'local_municipio',
        'local_cep',
        'local_logradoura_1',
        'local_bairro',
    ];

    protected $casts = [
        'data_fato'              => 'datetime',
        'data_inicio_atividade'  => 'datetime',
        'data_termino_atividade' => 'datetime',
        'com_ocorrencia_data'    => 'datetime',
        'nat_cobrade_id'         => 'integer',
    ];
}
