<?php

declare(strict_types=1);

namespace App\Modules\Rat\Models\Relatos;

use App\Modules\Rat\Models\RatOcorrencia;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vistoria técnica realizada durante uma ocorrência RAT.
 *
 * Campos-chave (ver migração 2026_02_10_134052):
 * @property int         $id
 * @property string|null $v_solicitante_nome
 * @property string|null $v_solicitante_cpf
 * @property string|null $v_tipo_imovel
 * @property string|null $v_estado_conservacao
 */
class RatRelatoVistoria extends RatRelato
{
    protected $table = 'rat_relato_vistoria';

    protected $fillable = [
        'ocorrencia_id',
        'created_by',
        'updated_by',
        // Solicitante
        'v_solicitante_nome',
        'v_solicitante_cpf',
        'v_solicitante_telefone',
        'v_solicitante_endereco',
        'v_solicitante_bairro',
        'v_solicitante_cep',
        // Local e imóvel
        'v_local_endereco',
        'v_local_complemento',
        'v_tipo_area',
        'v_tipo_area_descricao',
        'v_tipo_imovel',
        'v_tipo_imovel_outro_descricao',
        'v_tipo_construcao',
        'v_tipo_construcao_outro_descricao',
        'v_tipo_edificacao',
        'v_tipo_terreno_relevo',
        'v_sistema_estrutural',
        'v_numero_pavimentos',
        'v_estado_conservacao',
        'v_regime_ocupacao',
        // Moradores
        'v_proprietario_morador',
        'v_contato_telefone',
        'v_numero_moradores',
        'v_ha_idosos',
        'v_ha_criancas',
        'v_ha_dificuldade_locomocao',
        // Localização
        'v_endereco_imovel',
        'v_bairro',
        'v_municipio',
        'v_municipio_nome',
        'v_cep',
        'v_latitude',
        'v_longitude',
        // Infraestrutura
        'v_abastecimento_agua',
        'v_esgotamento_sanitario',
        'v_drenagem_superficial',
        'v_sistema_viario_acesso',
        'v_tipo_revestimento',
        'v_condicoes_acesso',
        'v_numero_moradias_terreno',
        'v_distancia_encosta',
        // Aspectos Técnicos
        'v_material_construtivo',
        'v_conservacao_estrutural',
        'v_elementos_estruturais',
        'v_elementos_construtivos',
        'v_agentes_potencializadores',
        'v_processos_geodinamicos',
        'v_danos_estruturais',
        'v_manutencao_uso_ocupacao',
        'v_historico',
        'v_tipo_destinacao',
        'v_tipo_localizacao',
        // Encaminhamentos
        'v_enc_interdicao_parcial',
        'v_enc_interdicao_total',
        'v_enc_remocao_temporaria',
        'v_enc_remocao_definitiva',
        'v_enc_isolamento_area',
        'v_enc_desocupacao_abrigo',
        'v_enc_notificacao_responsavel',
        'v_enc_contratacao_responsavel',
        'v_enc_comunicacao_orgaos',
        'v_enc_apoio_social',
        'v_enc_outros',
        'v_enc_outros_descricao',
        // Patologias
        'v_patologia_rachaduras',
        'v_patologia_trincas',
        'v_patologia_fissuras_estruturais',
        'v_patologia_deformacoes_estruturais',
        'v_patologia_infiltracoes',
        'v_patologia_corrosao_armaduras',
        'v_patologia_desagregacao',
        'v_patologia_eflorescencia',
        'v_patologia_desplacamento',
        'v_patologia_fundacoes',
        'v_patologia_instabilidade_talude',
        'v_patologia_movimentacao_solo',
        'v_patologia_tombamento_muralhas',
        'v_patologia_inundacoes',
        'v_patologia_alagamentos',
        'v_patologia_enxurradas',
        'v_patologia_madeira',
        'v_patologia_elementos_nao_estruturais',
        'v_patologia_falha_drenagem',
        'v_patologia_queda_arvores',
        'v_patologia_outros',
        'v_patologia_outros_descricao',
        // Bens Afetados
        'v_bens_residencia',
        'v_bens_muros',
        'v_bens_vias_publicas',
        'v_bens_pontes',
        'v_bens_viadutos',
        'v_bens_comercios',
        'v_bens_galpoes',
        'v_bens_predios_publicos',
        'v_bens_edificios_publicos',
        'v_bens_outros',
        'v_bens_outros_descricao',
        'v_bens_afetados',
        // Órgãos Acionados
        'v_orgao_copasa',
        'v_orgao_cemig',
        'v_orgao_secretaria_municipal',
        'v_orgao_defesa_civil_estadual',
        'v_orgao_dnit',
        'v_orgao_pm',
        'v_orgao_bm',
        'v_orgao_crea',
        'v_orgao_emater',
        'v_orgao_seapa',
        'v_orgao_outros',
        'v_orgao_outros_descricao',
        'v_orgaos_acionados',
        'v_orgaos_acionados_outros',
    ];

    protected $casts = [
        'v_numero_pavimentos'  => 'integer',
        'v_numero_moradores'   => 'integer',
        'v_latitude'           => 'decimal:8',
        'v_longitude'          => 'decimal:8',
    ];

    /**
     * Relação: Ocorrência pai
     */
    public function ocorrencia(): BelongsTo
    {
        return $this->belongsTo(RatOcorrencia::class, 'ocorrencia_id');
    }
}
