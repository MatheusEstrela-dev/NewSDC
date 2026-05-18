<?php

declare(strict_types=1);

namespace App\Modules\Rat\Models;

use App\Modules\Rat\Models\RatAnexo;
use App\Modules\Rat\Models\RatOcorrenciaHistorico;
use App\Modules\Rat\Models\RatOcorrenciaRelato;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RatOcorrencia extends Model
{
    use SoftDeletes, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

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

    public function ratAnexos(): HasMany
    {
        return $this->hasMany(RatAnexo::class, 'rat_id');
    }

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
        return $this->status === 1 ? 'Finalizado' : 'Em Andamento';
    }

    public function getProtocoloAttribute(): string
    {
        return $this->numero_bos ?? '';
    }

    // ─── Helpers internos para acessar relatosMorph respeitando eager-load ──

    private function relatosDe(string $type): \Illuminate\Support\Collection
    {
        if ($this->relationLoaded('relatosMorph')) {
            return $this->relatosMorph->where('conteudo_type', $type)->values();
        }
        return $this->relatosMorph()->where('conteudo_type', $type)->with('conteudo')->get();
    }

    private function primeiroDe(string $type): ?object
    {
        return $this->relatosDe($type)->first()?->conteudo;
    }

    // ─── Atributos computados ────────────────────────────────────────────────

    /** Cache interno para evitar múltiplas queries de dados gerais na mesma requisição. */
    private ?array $cachedDadosGerais = null;

    private function loadDadosGerais(): array
    {
        if ($this->cachedDadosGerais !== null) {
            return $this->cachedDadosGerais;
        }
        $conteudo                = $this->primeiroDe(\App\Modules\Rat\Models\Relatos\RatRelatoDadosGerais::class);
        $this->cachedDadosGerais = $conteudo ? $conteudo->toArray() : [];
        return $this->cachedDadosGerais;
    }

    public function getDadosGeraisAttribute(): array  { return $this->loadDadosGerais(); }
    public function getLocalAttribute(): array         { return $this->loadDadosGerais(); }
    public function getEnderecoAttribute(): array      { return $this->loadDadosGerais(); }
    public function getComunicacaoAttribute(): array   { return $this->loadDadosGerais(); }

    public function getRecursosAttribute(): array
    {
        return $this->relatosDe(\App\Modules\Rat\Models\Relatos\RatRelatoRecurso::class)
            ->map(function ($r) {
                $conteudo = $r->conteudo;
                if (!$conteudo) {
                    return null;
                }
                if (!$conteudo->relationLoaded('agentes')) {
                    $conteudo->load('agentes');
                }
                return $conteudo->toArray();
            })
            ->filter()
            ->values()
            ->toArray();
    }

    public function getEnvolvidosAttribute(): array
    {
        return $this->relatosDe(\App\Modules\Rat\Models\Relatos\RatRelatoEnvolvidos::class)
            ->map(fn ($r) => $r->conteudo?->toArray())
            ->filter()
            ->values()
            ->toArray();
    }

    public function getVistoriaAttribute(): array
    {
        $conteudo = $this->primeiroDe(\App\Modules\Rat\Models\Relatos\RatRelatoVistoria::class);
        return $conteudo ? $conteudo->toArray() : [];
    }

    public function getTemVistoriaAttribute(): bool
    {
        return !empty($this->getVistoriaAttribute());
    }
}
