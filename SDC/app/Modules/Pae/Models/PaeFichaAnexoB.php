<?php

declare(strict_types=1);

namespace App\Modules\Pae\Models;

use App\Models\Municipio;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PaeFichaAnexoB extends Model
{
    public $timestamps = false;

    protected $table = 'pae_fichas_anexo_b';

    protected $fillable = [
        'protocolo_id', 'versao', 'nome_barragem', 'nome_mina', 'metodo_construtivo',
        'volume_reservatorio', 'municipio_sede_id', 'municipio_sede_nome',
        'latitude', 'longitude', 'tipo_rejeito', 'toxicidade', 'extensao_zas_km',
        'populacao_zas', 'populacao_zas_mobilidade_reduzida', 'populacao_zss',
        'cursos_agua', 'edificacoes_hospitalares', 'edificacoes_escolares',
        'edificacoes_prisionais', 'edificacoes_outras', 'estruturas_associadas',
        'municipios_snapshot', 'criado_por', 'created_at',
    ];

    protected $casts = [
        'versao' => 'integer',
        'volume_reservatorio' => 'decimal:2',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'extensao_zas_km' => 'decimal:3',
        'populacao_zas' => 'integer',
        'populacao_zas_mobilidade_reduzida' => 'integer',
        'populacao_zss' => 'integer',
        'edificacoes_hospitalares' => 'integer',
        'edificacoes_escolares' => 'integer',
        'edificacoes_prisionais' => 'integer',
        'edificacoes_outras' => 'integer',
        'cursos_agua' => 'array',
        'estruturas_associadas' => 'array',
        'municipios_snapshot' => 'array',
        'created_at' => 'datetime',
    ];

    public function protocolo(): BelongsTo
    {
        return $this->belongsTo(PaeProtocolo::class, 'protocolo_id');
    }

    public function municipioSede(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'municipio_sede_id');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por');
    }
}
