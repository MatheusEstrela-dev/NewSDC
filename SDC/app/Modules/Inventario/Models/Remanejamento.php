<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Models;

use App\Models\User;
use App\Modules\Demandas\Models\Demanda;
use App\Modules\Inventario\Enums\StatusRemanejamento;
use App\Modules\Inventario\Enums\TipoMovimentacao;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Cabecalho de um lote de remanejamento. As movimentacoes do lote apontam para
 * ele por lote_id (nome herdado da coluna que ja existia).
 */
class Remanejamento extends Model
{
    use HasUuids;

    protected $table = 'inventario_ti_remanejamentos';

    protected $fillable = [
        'registrado_por_id', 'observacao', 'status', 'demanda_id', 'seplag_enviado_em',
        'seplag_enviado_por_id', 'seplag_envios', 'desfeito_em', 'desfeito_por_id',
    ];

    protected $attributes = [
        'status' => 'ativo',
        'seplag_envios' => 0,
    ];

    protected $casts = [
        'status' => StatusRemanejamento::class,
        'seplag_enviado_em' => 'datetime',
        'desfeito_em' => 'datetime',
        'seplag_envios' => 'integer',
    ];

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_id');
    }

    public function desfeitoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'desfeito_por_id');
    }

    public function seplagEnviadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seplag_enviado_por_id');
    }

    public function demanda(): BelongsTo
    {
        return $this->belongsTo(Demanda::class, 'demanda_id');
    }

    public function pessoas(): HasMany
    {
        return $this->hasMany(RemanejamentoPessoa::class, 'remanejamento_id');
    }

    public function movimentacoes(): HasMany
    {
        return $this->hasMany(Movimentacao::class, 'lote_id');
    }

    /** So os equipamentos remanejados: e o que conta como "item movido" e vai na planilha. */
    public function itensRemanejados(): HasMany
    {
        return $this->movimentacoes()->where('tipo', TipoMovimentacao::REMANEJAMENTO->value);
    }

    public function estaAtivo(): bool
    {
        return $this->status === StatusRemanejamento::ATIVO;
    }
}
