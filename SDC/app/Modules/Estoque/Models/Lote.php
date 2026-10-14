<?php

declare(strict_types=1);

namespace App\Modules\Estoque\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Lote de um produto num almoxarifado: validade, fornecedor/doador e saldo.
 * O saldo so muda pelo MovimentacaoEstoqueService.
 */
class Lote extends Model
{
    /** Validade a menos de N dias vira alerta (#33, "Controle de Validade"). */
    public const DIAS_ALERTA_VALIDADE = 30;

    protected $table = 'estoque_lotes';

    protected $fillable = [
        'produto_id', 'almoxarifado_id', 'codigo', 'validade', 'fornecedor', 'endereco', 'saldo', 'bloqueado',
    ];

    protected $casts = [
        'validade'  => 'date',
        'saldo'     => 'integer',
        'bloqueado' => 'boolean',
    ];

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class, 'produto_id')->withTrashed();
    }

    public function almoxarifado(): BelongsTo
    {
        return $this->belongsTo(Almoxarifado::class, 'almoxarifado_id');
    }

    /** Lote que pode sair: com saldo, fora de quarentena e dentro da validade. */
    public function scopeDisponivel(Builder $query): Builder
    {
        return $query->where('saldo', '>', 0)
            ->where('bloqueado', false)
            ->where(fn ($q) => $q->whereNull('validade')->orWhereDate('validade', '>=', Carbon::today()));
    }

    /** normal | atencao (vence em 30 dias) | critico (vencido) | quarentena */
    public function status(?Carbon $hoje = null): string
    {
        $hoje = ($hoje ?? Carbon::today())->copy()->startOfDay();

        return match (true) {
            $this->bloqueado                                      => 'quarentena',
            $this->validade !== null && $this->validade->lt($hoje) => 'critico',
            $this->validade !== null && $this->validade->lte($hoje->copy()->addDays(self::DIAS_ALERTA_VALIDADE)) => 'atencao',
            default                                                => 'normal',
        };
    }
}
