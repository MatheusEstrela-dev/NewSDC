<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Models;

use App\Models\Municipio;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int     $id
 * @property int     $ata_id
 * @property int     $municipio_id
 * @property int     $prestador_id
 * @property string  $numero
 * @property ?string $nome
 * @property ?string $contrato
 * @property float   $qtd_agua_m3
 * @property float   $valor_m3
 * @property bool    $ativo
 * @property ?string $observacoes
 */
class Lote extends Model
{
    use SoftDeletes;

    protected $table = 'tdap_lotes';

    protected $fillable = [
        'ata_id',
        'municipio_id',
        'prestador_id',
        'numero',
        'nome',
        'contrato',
        'qtd_agua_m3',
        'valor_m3',
        'ativo',
        'observacoes',
    ];

    protected $casts = [
        'ata_id'       => 'integer',
        'municipio_id' => 'integer',
        'prestador_id' => 'integer',
        'qtd_agua_m3'  => 'decimal:2',
        'valor_m3'     => 'decimal:2',
        'ativo'        => 'boolean',
    ];

    public function ata(): BelongsTo
    {
        return $this->belongsTo(Ata::class, 'ata_id');
    }

    /**
     * Municipio de referencia (coluna legada, anulavel). A lista real de
     * municipios atendidos pelo lote esta em {@see self::municipios()}.
     */
    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    /**
     * Municipios atendidos pelo lote. Um lote da ata agrupa varios municipios
     * ("...destinado aos municipios de A, B e C"); a coluna unica municipio_id
     * nao dava conta e deixava a grade sem municipio.
     */
    public function municipios(): BelongsToMany
    {
        return $this->belongsToMany(Municipio::class, 'tdap_lote_municipios', 'lote_id', 'municipio_id')
            ->orderBy('municipios.nome');
    }

    public function prestador(): BelongsTo
    {
        return $this->belongsTo(Prestador::class, 'prestador_id');
    }

    /**
     * Cronogramas emitidos sobre este lote. Existe para o guard de exclusao:
     * a FK e restrictOnDelete, entao apagar um lote com cronograma estourava
     * violacao de integridade no banco (500) em vez de mensagem de negocio.
     */
    public function cronogramas(): HasMany
    {
        return $this->hasMany(Cronograma::class, 'lote_id');
    }

    public function getValorTotalAttribute(): float
    {
        return (float) $this->qtd_agua_m3 * (float) $this->valor_m3;
    }

    public function scopeAtivo(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    public function scopeDaAta(Builder $query, int $ataId): Builder
    {
        return $query->where('ata_id', $ataId);
    }

    /** Filtra pelo vinculo N:N — o lote atende varios municipios. */
    public function scopeDoMunicipio(Builder $query, int $municipioId): Builder
    {
        return $query->whereHas(
            'municipios',
            fn (Builder $q) => $q->where('municipios.id', $municipioId),
        );
    }

    public function scopeDoPrestador(Builder $query, int $prestadorId): Builder
    {
        return $query->where('prestador_id', $prestadorId);
    }

    /** Busca textual por numero, nome ou contrato (mesmo padrao de Ata::scopeBuscar). */
    public function scopeBuscar(Builder $query, ?string $termo): Builder
    {
        if (! $termo) {
            return $query;
        }

        $like = '%'.mb_strtoupper($termo).'%';

        return $query->where(function (Builder $q) use ($like): void {
            $q->whereRaw('UPPER(numero) LIKE ?', [$like])
              ->orWhereRaw('UPPER(nome) LIKE ?', [$like])
              ->orWhereRaw('UPPER(contrato) LIKE ?', [$like]);
        });
    }
}
