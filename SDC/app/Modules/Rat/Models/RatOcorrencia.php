<?php

declare(strict_types=1);

namespace App\Models\Rat;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Rat\RatOcorrenciaHistorico;

/**
 * Entidade principal da nova estrutura de RAT.
 *
 * Relaciona-se com diversos tipos de relatos através de polimorfismo
 * via RatOcorrenciaRelato (tabela pivô polimórfica).
 *
 * @property int         $id
 * @property string|null $numero_bos
 * @property int         $status   0=Rascunho, 1=Finalizado
 * @property \Carbon\Carbon|null $prazo_edicao
 * @property string|null $historico
 * @property int|null    $ocorrencia_origem_id
 */
class RatOcorrencia extends Model
{
    use SoftDeletes;

    protected $table = 'rat_ocorrencias';

    protected $fillable = [
        'numero_bos',
        'sequencial_ano',
        'status',
        'prazo_edicao',
        'historico',
        'ocorrencia_origem_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status'       => 'integer',
        'prazo_edicao' => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relacionamentos
    // -------------------------------------------------------------------------

    /** Todos os relatos (entradas na tabela pivô) desta ocorrência. */
    public function relatos(): HasMany
    {
        return $this->hasMany(RatOcorrenciaRelato::class, 'ocorrencia_id');
    }

    /** Relatos com conteúdo polimórfico carregado (eager). */
    public function relatosMorph(): HasMany
    {
        return $this->hasMany(RatOcorrenciaRelato::class, 'ocorrencia_id')
                    ->with('conteudo');
    }

    /** Ocorrência de origem (BOS pai), quando houver desdobramento. */
    public function ocorrenciaOrigem(): BelongsTo
    {
        return $this->belongsTo(self::class, 'ocorrencia_origem_id');
    }

    /** Timeline de eventos desta ocorrência (imutável, crescente). */
    public function historico(): HasMany
    {
        return $this->hasMany(RatOcorrenciaHistorico::class, 'ocorrencia_id')
                    ->orderBy('created_at', 'asc');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isRascunho(): bool
    {
        return $this->status === 0;
    }

    public function isFinalizado(): bool
    {
        return $this->status === 1;
    }

    // -------------------------------------------------------------------------
    // Eventos de ciclo de vida
    // -------------------------------------------------------------------------

    /**
     * Cascata de soft delete: ao excluir (ocultar) uma ocorrência,
     * todos os relatos pivô associados também são soft-deletados.
     * Os dados permanecem no banco — apenas o deleted_at é preenchido.
     */
    protected static function booted(): void
    {
        static::deleting(function (self $ocorrencia): void {
            $ocorrencia->relatos()->each->delete();
        });
    }
}
