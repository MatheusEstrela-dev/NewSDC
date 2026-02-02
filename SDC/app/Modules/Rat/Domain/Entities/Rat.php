<?php

namespace App\Modules\Rat\Domain\Entities;

use App\Modules\Rat\Domain\ValueObjects\Protocolo;
use App\Modules\Rat\Domain\ValueObjects\Status;
use App\Modules\Rat\Domain\ValueObjects\Localizacao;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Rat extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', // Permitir atribuição de ID (UUID vindo do front)
        'protocolo',
        'status',
        'tem_vistoria',
        'dados_gerais',
        'local',
        'endereco',
        'comunicacao',
        'orgao_emissor_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'dados_gerais' => 'array',
        'local' => 'array',
        'endereco' => 'array',
        'comunicacao' => 'array',
        'tem_vistoria' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getProtocolo(): ?Protocolo
    {
        return $this->protocolo ? new Protocolo($this->protocolo) : null;
    }

    public function getStatus(): Status
    {
        return new Status($this->status ?? 'rascunho');
    }

    public function getLocalizacao(): ?Localizacao
    {
        if (!$this->local) {
            return null;
        }

        return new Localizacao(
            $this->local['municipio'] ?? null,
            $this->local['uf'] ?? null,
            $this->local['pais_id'] ?? 1
        );
    }

    /**
     * Órgão emissor do RAT (COMPDEC responsável)
     */
    public function orgaoEmissor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Modules\Compdec\Domain\Entities\Orgao::class, 'orgao_emissor_id');
    }
}

