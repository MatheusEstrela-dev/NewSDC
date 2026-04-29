<?php

declare(strict_types=1);

namespace App\Modules\Rat\Models\Relatos;

use App\Models\Rat\RatOcorrencia;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'ocorrencia_id',
        'seq',
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
        // Dados pessoais e documentos
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
        'p_cor_raca_cod',
        'p_sexo',
        'p_sexo_selecionado',
        'p_estado_civil',
        'p_orientacao_sexual',
        'p_identidade_genero',
        'p_nome_social',
        'p_nacionalidade',
        'p_pais_origem',
        'p_naturalidade_uf',
        'p_turista',
        'p_situacao_rua',
        // Endereço
        'p_end_cep',
        'p_end_pais',
        'p_end_estado_uf',
        'p_end_municipio',
        'p_end_bairro',
        'p_end_logradouro',
        'p_end_numero',
        'p_end_complemento',
        'p_end_km',
        'p_end_ibge',
        // Contato
        'p_telefone_residencial',
        'p_telefone_comercial',
        'p_email',
        'p_motivo_ausencia_contato',
        // Auditoria
        'created_by',
    ];

    protected $casts = [
        'p_data_nascimento'        => 'date',
        'g_envolvido_presenca'     => 'boolean',
        'g_envolvido_servico'      => 'boolean',
        'p_turista'                => 'boolean',
        'p_situacao_rua'           => 'boolean',
    ];

    /**
     * Relação: Ocorrência pai
     */
    public function ocorrencia(): BelongsTo
    {
        return $this->belongsTo(RatOcorrencia::class, 'ocorrencia_id');
    }
}
