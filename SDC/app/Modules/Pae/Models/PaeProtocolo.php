<?php

declare(strict_types=1);

namespace App\Modules\Pae\Models;

use App\Modules\Pae\Enums\PaeProtocoloStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class PaeProtocolo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pae_protocolos';

    protected $fillable = [
        'sigibar',
        'num_protocolo',
        'status',
        'situacao',
        'analista_atual_id',
        'user_id',
        'created_by',
        'updated_by',
        'pae_empnto_id',
        'protocolo_origem_id',
        'arquivado',
        'sei_numero',
        'dt_entrada',
        'limite_analise',
        'ccpae_venc',
        'ccpae',
        'obs',
        'empnto_search',
    ];

    protected $casts = [
        'status' => PaeProtocoloStatus::class,
        'arquivado' => 'boolean',
        'dt_entrada' => 'date',
        'limite_analise' => 'date',
        'ccpae_venc' => 'date',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\Pae\PaeProtocoloFactory::new();
    }

    public function analistaAtual(): BelongsTo
    {
        return $this->belongsTo(User::class, 'analista_atual_id');
    }

    public function empreendimento(): BelongsTo
    {
        return $this->belongsTo(PaeEmpnto::class, 'pae_empnto_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function origem(): BelongsTo
    {
        return $this->belongsTo(self::class, 'protocolo_origem_id');
    }

    public function versoes(): HasMany
    {
        return $this->hasMany(self::class, 'protocolo_origem_id');
    }

    public function tramitacoes(): HasMany
    {
        return $this->hasMany(PaeTramitacao::class, 'protocolo_id')->orderBy('dt_status', 'desc');
    }

    public function timeline(): HasMany
    {
        return $this->hasMany(PaeTimeline::class, 'protocolo_id')->orderBy('created_at', 'asc');
    }

    public function validarTransicaoStatus(PaeProtocoloStatus $novo): bool
    {
        return $this->status->canTransitionTo($novo);
    }

    public function scopeAtivo($query)
    {
        return $query->where('arquivado', false);
    }

    public function scopePorAnalista($query, int $analistaId)
    {
        return $query->where('analista_atual_id', $analistaId);
    }

    public function scopePorStatus($query, PaeProtocoloStatus $status)
    {
        return $query->where('status', $status->value);
    }
}
