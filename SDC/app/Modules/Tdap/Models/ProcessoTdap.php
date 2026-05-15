<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Models;

use App\Core\Outbox\RecordsDomainEvents;
use App\Models\Municipio;
use App\Models\User;
use App\Modules\Tdap\Enums\EstadoProcesso;
use App\Modules\Tdap\Enums\SwimlaneProcesso;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Agregado raiz do Workflow TDAP-Agua.
 *
 * Coordena o ciclo completo (Habilitacao -> Decretagem -> Licitacao ->
 * Execucao -> Liquidacao -> Pago -> Encerrado) e referencia entidades de
 * outros modulos (Decretacoes, PAE) sem acoplamento direto.
 *
 * @property string                 $id
 * @property string                 $numero
 * @property EstadoProcesso         $estado
 * @property SwimlaneProcesso       $swimlane_atual
 * @property int                    $municipio_id
 * @property ?int                   $decretacao_id
 * @property ?int                   $pae_form_id
 * @property ?array                 $contexto
 * @property \Carbon\Carbon         $aberto_em
 * @property ?\Carbon\Carbon        $encerrado_em
 * @property ?int                   $aberto_por
 */
class ProcessoTdap extends Model
{
    use SoftDeletes;
    use RecordsDomainEvents;

    protected $table = 'tdap_processos';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'numero',
        'estado',
        'swimlane_atual',
        'municipio_id',
        'decretacao_id',
        'pae_form_id',
        'contexto',
        'aberto_em',
        'encerrado_em',
        'aberto_por',
    ];

    protected $casts = [
        'estado'         => EstadoProcesso::class,
        'swimlane_atual' => SwimlaneProcesso::class,
        'municipio_id'   => 'integer',
        'decretacao_id'  => 'integer',
        'pae_form_id'    => 'integer',
        'contexto'       => 'array',
        'aberto_em'      => 'datetime',
        'encerrado_em'   => 'datetime',
        'aberto_por'     => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model): void {
            if (! $model->id) {
                $model->id = (string) Str::uuid();
            }
            if (! $model->aberto_em) {
                $model->aberto_em = now();
            }
        });
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    public function abertoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aberto_por');
    }

    /* Computed */

    public function getAggregateType(): string
    {
        return 'processo_tdap';
    }

    public function isTerminal(): bool
    {
        return $this->estado === EstadoProcesso::Encerrado;
    }

    /* Scopes */

    public function scopeAbertos(Builder $query): Builder
    {
        return $query->whereNull('encerrado_em');
    }

    public function scopeNoEstado(Builder $query, EstadoProcesso $estado): Builder
    {
        return $query->where('estado', $estado->value);
    }

    public function scopeNaSwimlane(Builder $query, SwimlaneProcesso $swimlane): Builder
    {
        return $query->where('swimlane_atual', $swimlane->value);
    }
}
