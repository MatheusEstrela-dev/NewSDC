<?php

namespace App\Modules\Rat\Models;

use App\Modules\Rat\Enums\Localizacao;
use App\Modules\Rat\Enums\Protocolo;
use App\Modules\Rat\Enums\Status;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rat extends Model
{
    use HasUuids; //, SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'protocolo',
        'status',
        'tem_vistoria',
        'dados_gerais',
        'local',
        'endereco',
        'comunicacao',
        'recursos',
        'envolvidos',
        'vistoria',
        'historico',
        'anexos',
        'orgao_emissor_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'dados_gerais' => 'array',
        'local' => 'array',
        'endereco' => 'array',
        'comunicacao' => 'array',
        'recursos' => 'array',
        'envolvidos' => 'array',
        'vistoria' => 'array',
        'historico' => 'array',
        'anexos' => 'array',
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
        return Status::tryFrom($this->status ?? 'rascunho') ?? Status::RASCUNHO;
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
     * Orgao emissor do RAT (COMPDEC responsavel).
     * @deprecated Aguardando criação de Orgao model
     */
    // public function orgaoEmissor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    // {
    //     return $this->belongsTo(\App\Modules\Compdec\Models\Orgao::class, 'orgao_emissor_id');
    // }

    /**
     * Usuário que criou o RAT.
     */
    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Usuário que realizou a última atualização do RAT.
     */
    public function updater(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }
}


