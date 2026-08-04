<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Models;

use App\Modules\Decretacoes\Support\Vigencia;
use App\Modules\Notificacoes\Contracts\Rastreavel;
use App\Modules\Notificacoes\Support\TrilhaDeAcoes;
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
class Processo extends Model implements Rastreavel
{
    use HasFactory, SoftDeletes, TrilhaDeAcoes;

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
        // Campos adicionais para edicao
        'redec_id',
        'n_decreto_estadual',
        'n_edicao_domg',
        'data_decreto_estadual',
        'data_publicacao_domg',
        'data_portaria_federal',
        'n_edicao_dou',
        'data_publicacao_dou',
    ];

    protected $casts = [
        'data_entrada' => 'date',
        'data_ocorrencia_desastre' => 'date',
        'data_decreto_municipal' => 'date',
        'data_publicacao_mg' => 'date',
        'data_decreto_estadual' => 'date',
        'data_publicacao_domg' => 'date',
        'data_portaria_federal' => 'date',
        'data_publicacao_dou' => 'date',
        'prazo_vigencia' => 'integer',
        'tipo_desastre_id' => 'integer',
        'redec_id' => 'integer',
    ];

    protected $appends = [
        'data_vencimento',
        'dias_restantes',
        'prazo_vigencia_efetivo',
        'vigente',
        'vencido',
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
            if (!$model->created_by) {
                if (Auth::check()) {
                    $model->created_by = Auth::id();
                } else {
                    throw new \RuntimeException('Usuario nao autenticado para criar processo de decretacao.');
                }
            }
        });

        static::created(function ($model) {
            $model->logChange('created');
        });

        static::updated(function ($model) {
            $model->logChange('updated');
        });

        static::deleting(function ($model) {
            $model->desastres()->each(function ($desastre) {
                $desastre->entradas()->delete();
            });
            $model->desastres()->delete();
            $model->decretoMunicipios()->delete();
        });

        static::deleted(function ($model) {
            $model->logChange('deleted');
            // Invalida cache de estatisticas quando um processo e removido
            try {
                app(\App\Modules\Decretacoes\Services\ProcessoStatsService::class)->clearCache();
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Erro ao limpar cache de estatisticas: ' . $e->getMessage());
            }
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

    /**
     * Prazo de vigencia efetivamente usado no calculo (180 dias por padrao).
     */
    public function getPrazoVigenciaEfetivoAttribute(): int
    {
        return Vigencia::prazo($this->attributes['prazo_vigencia'] ?? null);
    }

    public function getDataVencimentoAttribute(): ?Carbon
    {
        return Vigencia::vencimento($this->data_publicacao_mg, $this->attributes['prazo_vigencia'] ?? null);
    }

    /**
     * Dias restantes assinados: negativo = vencido, 0 = vence hoje, null = sem publicacao.
     */
    public function getDiasRestantesAttribute(): ?int
    {
        return Vigencia::diasRestantes($this->data_publicacao_mg, $this->attributes['prazo_vigencia'] ?? null);
    }

    public function getVigenteAttribute(): bool
    {
        return Vigencia::isVigente($this->data_publicacao_mg, $this->attributes['prazo_vigencia'] ?? null);
    }

    public function getVencidoAttribute(): bool
    {
        return Vigencia::isVencido($this->data_publicacao_mg, $this->attributes['prazo_vigencia'] ?? null);
    }

    public function getProximoVencerAttribute(): bool
    {
        return Vigencia::isProximoVencer($this->data_publicacao_mg, $this->attributes['prazo_vigencia'] ?? null);
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
        $vencimento = Vigencia::sqlVencimento();

        return $query->where(function ($q) use ($vencimento) {
            $q->whereNull('data_publicacao_mg')
              ->orWhereRaw("{$vencimento} >= CURRENT_DATE");
        });
    }

    public function scopeVencidos($query)
    {
        return $query->whereNotNull('data_publicacao_mg')
                     ->whereRaw(Vigencia::sqlVencimento() . ' < CURRENT_DATE');
    }

    public function scopeProximosVencer($query)
    {
        $janela = Vigencia::JANELA_PROXIMO_VENCER_DIAS;

        return $query->whereNotNull('data_publicacao_mg')
                     ->whereRaw('(' . Vigencia::sqlVencimento() . " - CURRENT_DATE) BETWEEN 0 AND {$janela}");
    }

    public function podeSerEditado(): bool
    {
        $status = $this->reconhecimento ?? '';
        return !str_contains(strtolower($status), 'reconhecido');
    }

    public function podeSerExcluido(): bool
    {
        return true;
    }

    protected function logChange(string $action): void
    {
        ProcessoLog::create([
            'uuid'                 => (string) Str::uuid7(),
            'entrada_processo_id'  => $this->id,
            'entrada_processo_data' => $this->toArray(),
            'action'               => $action,
        ]);
    }

    // ─── Trilha de acoes (notificacao ao dono) ──────────────────────────────

    public function moduloNotificacao(): string
    {
        return 'decretacoes';
    }

    public function rotuloProtocolo(): string
    {
        foreach ([$this->processo, $this->n_protocolo_fide] as $identificador) {
            $valor = trim((string) $identificador);

            if ($valor !== '') {
                return 'Processo '.$valor;
            }
        }

        return 'Processo de '.($this->created_at?->format('d/m/Y') ?? 'data nao informada');
    }

    /**
     * @return list<int>
     */
    public function donosNotificacao(): array
    {
        return $this->created_by === null ? [] : [(int) $this->created_by];
    }

    public function urlNotificacao(): ?string
    {
        return '/decretacoes/'.$this->getKey();
    }

    public function campoSituacao(): ?string
    {
        return 'status';
    }

    /**
     * O status do processo e texto livre no banco, ja no formato que a tela mostra
     * ("Enviado para Publicacao", "Nao reconhecido pelo Estado").
     */
    public function rotuloSituacao(): ?string
    {
        $status = trim((string) $this->status);

        return $status === '' ? null : $status;
    }

    /**
     * @return list<string>
     */
    public function camposIgnoradosNaTrilha(): array
    {
        return array_merge($this->camposBaseIgnoradosNaTrilha(), [
            // Geometria da area afetada e payload tecnico, nao leitura para o dono.
            'area_afetada_geom',
            // Espelho denormalizado do tipo de desastre.
            'tipo_desastre_nome',
        ]);
    }
}

/*
 * DecretoMunicipio model.
 */
class DecretoMunicipio extends Model
{
    use SoftDeletes;

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
    use SoftDeletes;

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
