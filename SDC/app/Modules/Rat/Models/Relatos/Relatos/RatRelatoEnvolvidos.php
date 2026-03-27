<?php

declare(strict_types=1);

namespace App\Models\Rat\Relatos;

/**
 * Envolvidos (vítimas, agentes, etc.) em uma ocorrência RAT.
 *
 * Campos-chave (ver migração 2026_02_10_133452):
 * @property int         $id
 * @property string|null $p_nome_completo   Nome completo / razão social
 * @property string|null $g_tipo_pessoa     Tipo de pessoa envolvida
 * @property string|null $p_cpf
 * @property string|null $p_sexo
 */
class RatRelatoEnvolvidos extends RatRelato
{
    protected $table = 'rat_relato_envolvidos';

    protected $fillable = [
        // Dados gerais do envolvimento
        'g_tipo_pessoa',
        'g_lesao_grau',
        'g_lesao_grau_selecionado',
        'g_atendimento_vitima_repassada',
        'g_envolvido_presenca',
        'g_envolvido_tipo',
        'g_envolvido_orgao',
        'g_envolvido_uf',
        'g_envolvido_matricula',
        'g_envolvido_servico',
        // Dados pessoais
        'p_tipo',
        'p_tipo_selecionado',
        'p_numero',
        'p_orgao_expedidor',
        'p_nome_completo',
        'p_nome_fantasia',
        'p_data_nascimento',
        'p_cpf',
        'p_nome_mae',
        'p_nome_pai',
        'p_ocupacao_atual',
        'p_escolaridade',
        'p_cor_raca',
        'p_sexo',
        'p_estado_civil',
        'p_nacionalidade',
        // Endereço
        'p_end_cep',
        'p_end_pais',
        'p_end_estado_uf',
    ];

    protected $casts = [
        'p_data_nascimento'        => 'date',
        'g_envolvido_presenca'     => 'boolean',
        'g_envolvido_servico'      => 'boolean',
        'p_turista'                => 'boolean',
        'p_situacao_rua'           => 'boolean',
    ];
}
