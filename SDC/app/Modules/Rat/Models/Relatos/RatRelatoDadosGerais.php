<?php

declare(strict_types=1);

namespace App\Modules\Rat\Models\Relatos;

use App\Models\Rat\RatOcorrencia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RatRelatoDadosGerais - Relato com dados gerais da ocorrência
 */
class RatRelatoDadosGerais extends RatRelato
{
    use HasFactory;

    protected $table = 'rat_relato_dados_gerais';

    protected $fillable = [
        'ocorrencia_id',
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
        'local_complemento',
        'local_numero',
        'local_km',
        'local_ponto_referencia',
        'local_latitude',
        'local_longitude',
        'local_ocorrencia_tipo',
        'local_ocorrencia_estradas_rodovias',
        'local_unidade_militar',
        'tem_vistoria',
        'descricao', // Narrative history
        'status',
    ];

    protected $casts = [
        'data_fato' => 'datetime',
        'data_inicio_atividade' => 'datetime',
        'data_termino_atividade' => 'datetime',
        'com_ocorrencia_data' => 'datetime',
        'local_latitude' => 'float',
        'local_longitude' => 'float',
        'tem_vistoria' => 'boolean',
    ];

    /**
     * Relação: Ocorrência pai
     */
    public function ocorrencia(): BelongsTo
    {
        return $this->belongsTo(RatOcorrencia::class, 'ocorrencia_id');
    }

    /**
     * Relação: Envolvidos
     */
    public function envolvidos(): HasMany
    {
        return $this->hasMany(RatRelatoEnvolvidos::class, 'ocorrencia_id');
    }

    /**
     * Relação: Recursos
     */
    public function recursos(): HasMany
    {
        return $this->hasMany(RatRelatoRecurso::class, 'ocorrencia_id');
    }

    /**
     * Relação: Vistoria
     */
    public function vistoria(): HasMany
    {
        return $this->hasMany(RatRelatoVistoria::class, 'ocorrencia_id');
    }
}
