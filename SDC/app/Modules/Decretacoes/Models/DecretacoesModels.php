<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Main Processo model for the Decretacoes module.
 */
class Processo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'dec_entrada_processos';

    protected $fillable = [
        'data_entrada',
        'data_ocorrencia_desastre',
        'processo',
        'analista',
        'n_protocolo_fide',
        'decreto_municipal',
        'tipo_desastre_id',
        'tipo_desastre',
        'tipo_desastre_nome',
        'tipo_decreto',
        'data_decreto_municipal',
        'data_publicacao_mg',
        'prazo_vigencia',
        'status',
        'situacao_processo',
        'reconhecimento',
        'reconhecimento_decreto_n_data',
        'data_publicacao_diario',
        'portaria_reconhecimento_fed',
        'portaria_diario_oficial',
        'reconhecimento_federal',
        'observacoes',
        'processo_inserido_sei',
        'area_afetada_geom',
        'orgao_responsavel_id',
        'created_by',
    ];

    protected $casts = [
        'data_entrada' => 'date',
        'data_ocorrencia_desastre' => 'date',
        'data_decreto_municipal' => 'date',
        'data_publicacao_mg' => 'date',
        'data_publicacao_diario' => 'date',
        'prazo_vigencia' => 'integer',
        'tipo_desastre_id' => 'integer',
    ];

    protected $appends = [
        'data_vencimento',
        'dias_restantes',
        'vigente',
        'proximo_vencer',
        'situacao_anormalidade',
        'tipo_desastre_nome',
        'tipo_desastre_cobrade',
    ];

    protected $with = ['municipios', 'desastres'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (Auth::check() && !$model->created_by) {
                $model->created_by = Auth::id();
            }
        });

        static::created(function ($model) {
            $model->logChange('created');
        });

        static::updated(function ($model) {
            $model->logChange('updated');
        });

        static::deleted(function ($model) {
            $model->logChange('deleted');
        });
    }

    public function municipios(): HasManyThrough
    {
        return $this->hasManyThrough(
            \App\Models\Municipio::class,
            DecretoMunicipio::class,
            'entrada_processos_id',
            'id',
            'id',
            'municipio_id'
        )->orderBy('nome', 'asc');
    }

    public function decretoMunicipios(): HasMany
    {
        return $this->hasMany(DecretoMunicipio::class, 'entrada_processos_id');
    }

    public function desastres(): HasMany
    {
        return $this->hasMany(EntradaCategoriaDesastre::class, 'entrada_processo_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ProcessoLog::class, 'entrada_processo_id')->orderBy('created_at', 'desc');
    }

    public function orgaoResponsavel(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Compdec\Domain\Entities\Orgao::class, 'orgao_responsavel_id');
    }

    public function getDataVencimentoAttribute(): ?Carbon
    {
        if (!$this->data_publicacao_mg || !$this->prazo_vigencia) {
            return null;
        }

        return $this->data_publicacao_mg->addDays($this->prazo_vigencia);
    }

    public function getDiasRestantesAttribute(): ?int
    {
        if (!$this->data_vencimento) {
            return null;
        }

        $hoje = Carbon::today();
        $diff = $hoje->diffInDays($this->data_vencimento, false);

        return $diff < 0 ? 0 : (int) $diff;
    }

    public function getVigenteAttribute(): bool
    {
        if (!$this->data_vencimento) {
            return true;
        }

        return $this->data_vencimento->isFuture();
    }

    public function getProximoVencerAttribute(): bool
    {
        if (!$this->dias_restantes) {
            return false;
        }

        return $this->dias_restantes > 0 && $this->dias_restantes <= 15;
    }

    public function getSituacaoAnormalidadeAttribute(): ?string
    {
        return $this->attributes['tipo_desastre'] ?? null;
    }

    public function getTipoDesastreNomeAttribute(): ?string
    {
        if (!$this->tipo_desastre_id) {
            return $this->attributes['tipo_desastre'] ?? 'N/A';
        }

        $cobrade = include app_path('Enums/classificacao_desastres.php');

        foreach ($cobrade as $item) {
            if ($item['id'] == $this->tipo_desastre_id) {
                return $item['a_definicao'] ?? $item['subtipo'] ?? $item['tipo'] ?? $item['subgrupo'] ?? $item['grupo'] ?? 'N/A';
            }
        }

        return $this->attributes['tipo_desastre'] ?? 'N/A';
    }

    public function getTipoDesastreCobradeAttribute(): ?string
    {
        if (!$this->tipo_desastre_id) {
            return null;
        }

        $cobrade = include app_path('Enums/classificacao_desastres.php');

        foreach ($cobrade as $item) {
            if ($item['id'] == $this->tipo_desastre_id) {
                return $item['cobrade'];
            }
        }

        return null;
    }

    public function getDesastreAttribute()
    {
        if (!$this->tipo_desastre_id) {
            return $this->attributes['tipo_desastre'] ?? 'N/A';
        }

        $cobrade = include app_path('Enums/classificacao_desastres.php');

        foreach ($cobrade as $item) {
            if ($item['id'] == $this->tipo_desastre_id) {
                return $item;
            }
        }

        return $this->attributes['tipo_desastre'] ?? 'N/A';
    }

    public function scopeVigentes($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('data_publicacao_mg')
              ->orWhereRaw('DATE_ADD(data_publicacao_mg, INTERVAL prazo_vigencia DAY) >= CURDATE()');
        });
    }

    public function scopeVencidos($query)
    {
        return $query->whereNotNull('data_publicacao_mg')
                     ->whereRaw('DATE_ADD(data_publicacao_mg, INTERVAL prazo_vigencia DAY) < CURDATE()');
    }

    public function scopeProximosVencer($query)
    {
        return $query->whereNotNull('data_publicacao_mg')
                     ->whereRaw('DATEDIFF(DATE_ADD(data_publicacao_mg, INTERVAL prazo_vigencia DAY), CURDATE()) BETWEEN 1 AND 15');
    }

    public function podeSerEditado(): bool
    {
        $status = $this->reconhecimento ?? '';
        return !str_contains(strtolower($status), 'reconhecido');
    }

    public function podeSerExcluido(): bool
    {
        $status = $this->reconhecimento ?? '';
        return in_array($status, ['Registro', '']);
    }

    protected function logChange(string $action): void
    {
        ProcessoLog::create([
            'uuid'                 => (string) Str::uuid(),
            'entrada_processo_id'  => $this->id,
            'entrada_processo_data' => $this->toArray(),
            'action'               => $action,
        ]);
    }
}

/**
 * DecretoMunicipio model.
 */
class DecretoMunicipio extends Model
{
    protected $table = 'dec_decreto_municipios';

    protected $fillable = [
        'entrada_processos_id',
        'municipio_id',
        'n_protocolo_fide',
    ];

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Municipio::class, 'municipio_id');
    }

    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class, 'entrada_processos_id');
    }
}

/**
 * DesastreGrupo model.
 */
class DesastreGrupo extends Model
{
    protected $table = 'dec_desastre_grupos';

    public function desastres(): HasMany
    {
        return $this->hasMany(DesastreCategoria::class, 'desastre_grupo_id');
    }
}

/**
 * DesastreCategoria model.
 */
class DesastreCategoria extends Model
{
    protected $table = 'dec_desastre_categorias';

    protected $fillable = [
        'titulo',
        'informacao',
        'descricao',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(DesastreItem::class, 'categoria_id');
    }
}

/**
 * DesastreItem model.
 */
class DesastreItem extends Model
{
    protected $table = 'dec_desastre_items';

    protected $fillable = [
        'categoria_id',
        'titulo',
        'observacao',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(DesastreCategoria::class, 'categoria_id');
    }

    public function campos(): HasMany
    {
        return $this->hasMany(DesastreItemCampo::class, 'desastre_item_id');
    }
}

/**
 * DesastreItemCampo model.
 */
class DesastreItemCampo extends Model
{
    protected $table = 'dec_desastre_item_campos';

    protected $fillable = [
        'desastre_item_id',
        'titulo',
        'tipo',
        'observacao',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(DesastreItem::class, 'desastre_item_id');
    }
}

/**
 * EntradaCategoriaDesastre model.
 */
class EntradaCategoriaDesastre extends Model
{
    protected $table = 'dec_entrada_categoria_desastres';

    protected $fillable = [
        'municipio_id',
        'categoria_id',
        'entrada_processo_id',
        'descricao',
    ];

    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class, 'entrada_processo_id');
    }

    public function entradas(): HasMany
    {
        return $this->hasMany(EntradaDesastre::class, 'entrada_categoria_desastre_id');
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Municipio::class, 'municipio_id');
    }
}

/**
 * EntradaDecreto model.
 */
class EntradaDecreto extends Model
{
    protected $table = 'dec_entrada_decretos';

    protected $fillable = [
        'entrada_processos_id',
        'decreto_categoria_id',
        'observacao',
    ];

    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class, 'entrada_processos_id');
    }
}

/**
 * EntradaDesastre model.
 */
class EntradaDesastre extends Model
{
    use SoftDeletes;

    protected $table = 'dec_entrada_desastres';

    protected $fillable = [
        'municipio_id',
        'item_campo_id',
        'entrada_processo_id',
        'item_id',
        'entrada_categoria_desastre_id',
        'campo_titulo',
        'valor',
    ];

    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class, 'entrada_processo_id');
    }

    public function categoriaDesastre(): BelongsTo
    {
        return $this->belongsTo(EntradaCategoriaDesastre::class, 'entrada_categoria_desastre_id');
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Municipio::class, 'municipio_id');
    }
}

/**
 * ProcessoLog model.
 */
class ProcessoLog extends Model
{
    protected $table = 'dec_entrada_processo_logs';

    protected $fillable = [
        'entrada_processo_id',
        'entrada_processo_data',
        'action',
        'uuid',
    ];

    protected $casts = [
        'entrada_processo_data' => 'array',
    ];

    public $timestamps = true;

    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class, 'entrada_processo_id');
    }
}
