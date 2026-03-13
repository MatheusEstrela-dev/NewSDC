<?php

declare(strict_types=1);

namespace App\Models\Rat;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Tabela pivô polimórfica que liga uma RatOcorrencia ao conteúdo específico
 * (RatRelatoDadosGerais, RatRelatoEnvolvidos, RatRelatoRecurso, RatRelatoVistoria).
 *
 * @property int         $id
 * @property int|null    $ocorrencia_id
 * @property int|null    $conteudo_id
 * @property string|null $conteudo_type  FQCN do modelo polimórfico
 */
class RatOcorrenciaRelato extends Model
{
    use SoftDeletes;

    protected $table = 'rat_ocorrencia_relatos';

    protected $fillable = [
        'ocorrencia_id',
        'conteudo_id',
        'conteudo_type',
        'created_by',
    ];

    // -------------------------------------------------------------------------
    // Relacionamentos
    // -------------------------------------------------------------------------

    /** Ocorrência à qual este relato pertence. */
    public function ocorrencia(): BelongsTo
    {
        return $this->belongsTo(RatOcorrencia::class, 'ocorrencia_id');
    }

    /**
     * Conteúdo polimórfico: resolve para o modelo concreto de relato
     * (DadosGerais | Envolvidos | Recurso | Vistoria).
     */
    public function conteudo(): MorphTo
    {
        return $this->morphTo('conteudo');
    }

    // -------------------------------------------------------------------------
    // Eventos de ciclo de vida
    // -------------------------------------------------------------------------

    /**
     * Cascata de soft delete: ao excluir este pivô,
     * o conteúdo polimórfico associado também é soft-deletado.
     * Os dados permanecem no banco — apenas o deleted_at é preenchido.
     */
    protected static function booted(): void
    {
        static::deleting(function (self $relato): void {
            $relato->conteudo?->delete();
        });
    }
}
