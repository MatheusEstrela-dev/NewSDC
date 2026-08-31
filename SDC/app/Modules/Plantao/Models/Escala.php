<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Models;

use App\Models\User;
use App\Modules\Plantao\Enums\StatusEscala;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A escala de um mes: o planejamento de quem deveria trabalhar.
 *
 * Nao confundir com Plantao, que e a execucao. A ligacao entre as duas e
 * plantoes.escala_item_id, e e ela que responde "quem faltou".
 */
class Escala extends Model
{
    use SoftDeletes;

    protected $table = 'plantao_escalas';

    protected $fillable = [
        'ano',
        'mes',
        'titulo',
        'status',
        'publicada_em',
        'publicada_por_id',
        'criada_por_id',
        'observacoes',
    ];

    protected $casts = [
        'status' => StatusEscala::class,
        'publicada_em' => 'datetime',
        'ano' => 'integer',
        'mes' => 'integer',
    ];

    public function itens(): HasMany
    {
        return $this->hasMany(EscalaItem::class, 'escala_id');
    }

    public function publicadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'publicada_por_id');
    }

    public function criadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criada_por_id');
    }

    public function scopePublicadas(Builder $query): Builder
    {
        return $query->where('status', StatusEscala::PUBLICADA->value);
    }

    public function scopeDoMes(Builder $query, int $ano, int $mes): Builder
    {
        return $query->where('ano', $ano)->where('mes', $mes);
    }

    /**
     * Primeiro e ultimo dia do mes da escala. Usado para montar a janela do
     * calendario e para validar que um item nao caiu fora do proprio mes.
     */
    public function primeiroDia(): Carbon
    {
        return Carbon::create($this->ano, $this->mes, 1)->startOfDay();
    }

    public function ultimoDia(): Carbon
    {
        return $this->primeiroDia()->endOfMonth()->startOfDay();
    }

    public function contemData(Carbon $data): bool
    {
        return $data->between($this->primeiroDia(), $this->ultimoDia());
    }

    /**
     * "Setembro/2026".
     */
    public function rotulo(): string
    {
        if (is_string($this->titulo) && trim($this->titulo) !== '') {
            return $this->titulo;
        }

        $meses = [
            1 => 'Janeiro', 'Fevereiro', 'Marco', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro',
        ];

        return ($meses[$this->mes] ?? (string) $this->mes).'/'.$this->ano;
    }
}
