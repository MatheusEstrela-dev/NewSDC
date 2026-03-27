<?php

declare(strict_types=1);

namespace App\Models\Rat\Relatos;

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
        'v_cep',
        'v_latitude',
        'v_longitude',
    ];

    protected $casts = [
        'v_numero_pavimentos'  => 'integer',
        'v_numero_moradores'   => 'integer',
        'v_latitude'           => 'decimal:8',
        'v_longitude'          => 'decimal:8',
    ];
}
