<?php

declare(strict_types=1);

namespace App\Modules\NotasTecnicas\Models;

use App\Models\User;
use App\Modules\NotasTecnicas\Enums\NotaTecnicaStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotaTecnica extends Model
{
    use SoftDeletes;

    protected $table = 'notas_tecnicas';

    protected $fillable = [
        'codigo',
        'titulo',
        'categoria_id',
        'resumo',
        'status',
        'vigencia_inicio',
        'elaborado_por',
        'aprovado_por',
        'rotas_relacionadas',
    ];

    protected $casts = [
        'status' => NotaTecnicaStatus::class,
        'vigencia_inicio' => 'date',
        'rotas_relacionadas' => 'array',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(NotaTecnicaCategoria::class, 'categoria_id');
    }

    /** Mais recente primeiro: a primeira e a versao corrente. */
    public function versoes(): HasMany
    {
        return $this->hasMany(NotaTecnicaVersao::class)->orderByDesc('created_at')->orderByDesc('id');
    }

    public function versaoAtual(): HasOne
    {
        return $this->hasOne(NotaTecnicaVersao::class)->ofMany(['created_at' => 'max', 'id' => 'max']);
    }

    public function elaboradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'elaborado_por');
    }

    public function aprovadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprovado_por');
    }

    /** @param Builder<self> $query */
    public function scopeVisiveisParaLeitor(Builder $query): void
    {
        $query->whereIn('status', NotaTecnicaStatus::visiveisParaLeitor());
    }
}
