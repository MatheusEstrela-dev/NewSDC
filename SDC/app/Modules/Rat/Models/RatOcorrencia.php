<?php

declare(strict_types=1);

namespace App\Modules\Rat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RatOcorrencia extends Model
{
    use SoftDeletes;

    protected $table = 'rat_ocorrencias';

    protected $fillable = [
        'numero_bos',
        'sequencial_ano',
        'created_by',
        'status',
        'prazo_edicao',
        'updated_by',
        'ocorrencia_origem_id',
        'historico',
        'anexos',
    ];

    protected $casts = [
        'status'       => 'integer',
        'prazo_edicao' => 'datetime',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
        'deleted_at'   => 'datetime',
        'anexos'       => 'array',
        'historico'    => 'array',
    ];

    protected $appends = ['protocolo'];

    // ─── Relacionamentos ────────────────────────────────────────────────────

    public function relatosMorph(): HasMany
    {
        return $this->hasMany(RatOcorrenciaRelato::class, 'ocorrencia_id');
    }

    public function relatos_ocorrencia(): HasMany
    {
        return $this->hasMany(RatOcorrenciaRelato::class, 'ocorrencia_id');
    }

    public function historicos(): HasMany
    {
        return $this->hasMany(RatOcorrenciaHistorico::class, 'ocorrencia_id');
    }

    public function ocorrenciaOrigem(): BelongsTo
    {
        return $this->belongsTo(self::class, 'ocorrencia_origem_id');
    }

    public function ocorrenciasFilhas(): HasMany
    {
        return $this->hasMany(self::class, 'ocorrencia_origem_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    public function orgaoEmissor(): BelongsTo
    {
        // Compatibilidade: retorna relação vazia se módulo Compdec não disponível
        try {
            if (class_exists(\App\Modules\Compdec\Models\Orgao::class)) {
                return $this->belongsTo(\App\Modules\Compdec\Models\Orgao::class, 'orgao_emissor_id');
            }
        } catch (\Throwable) {}
        return $this->belongsTo(self::class, 'orgao_emissor_id')->whereRaw('1=0');
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    public function isFinalizada(): bool
    {
        return $this->status === 1;
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status === 1 ? 'Finalizado' : 'Rascunho';
    }

    public function getProtocoloAttribute(): string
    {
        if (!$this->numero_bos) {
            return '';
        }
        return $this->numero_bos;
    }

    public function getDadosGeraisAttribute(): array
    {
        $dados = $this->relatosMorph()
            ->where('conteudo_type', \App\Modules\Rat\Models\Relatos\RatRelatoDadosGerais::class)
            ->first()?->conteudo;
        return $dados ? $dados->toArray() : [];
    }

    public function getLocalAttribute(): array
    {
        return $this->getDadosGeraisAttribute();
    }

    public function getEnderecoAttribute(): array
    {
        return $this->getDadosGeraisAttribute();
    }

    public function getComunicacaoAttribute(): array
    {
        return $this->getDadosGeraisAttribute();
    }

    public function getRecursosAttribute(): array
    {
        return $this->relatosMorph()
            ->where('conteudo_type', \App\Modules\Rat\Models\Relatos\RatRelatoRecurso::class)
            ->get()
            ->map(function ($r) {
                $conteudo = $r->conteudo;
                if ($conteudo) {
                    $conteudo->load('agentes');
                    return $conteudo->toArray();
                }
                return null;
            })
            ->filter()
            ->values()
            ->toArray();
    }

    public function getEnvolvidosAttribute(): array
    {
        return $this->relatosMorph()
            ->where('conteudo_type', \App\Modules\Rat\Models\Relatos\RatRelatoEnvolvidos::class)
            ->get()
            ->map(fn ($r) => $r->conteudo)
            ->filter()
            ->values()
            ->toArray();
    }

    public function getVistoriaAttribute(): array
    {
        $vistoria = $this->relatosMorph()
            ->where('conteudo_type', \App\Modules\Rat\Models\Relatos\RatRelatoVistoria::class)
            ->first()?->conteudo;
        return $vistoria ? $vistoria->toArray() : [];
    }

    public function getTemVistoriaAttribute(): bool
    {
        return !empty($this->getVistoriaAttribute());
    }
}
