<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Models;

use App\Models\User;
use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Enums\ItemInstalacao;
use App\Modules\Notificacoes\Contracts\Rastreavel;
use App\Modules\Notificacoes\Support\TrilhaDeAcoes;
use Database\Factories\Cisterna\CisternaVistoriaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Uma etapa da cadeia de vistoria. Colapsa as tres tabelas do legado:
 * sinc_cisterna_rel_fornecedor, sinc_cisterna_rel_compdec e
 * sinc_cisterna_rel_cedec.
 *
 * @property int            $id
 * @property EtapaVistoria  $etapa
 * @property ?int           $numero_instalacao
 */
class CisternaVistoria extends Model implements HasMedia, Rastreavel
{
    use HasFactory;
    use InteractsWithMedia;
    use SoftDeletes;
    use TrilhaDeAcoes;

    protected $table = 'cisterna_vistorias';

    protected $fillable = [
        'beneficiario_id', 'etapa', 'numero_instalacao',
        'engenheiro_nome', 'engenheiro_crea', 'engenheiro_art',
        'data_relatorio', 'local_relatorio',
        'processo_sei', 'contrato', 'empenho', 'placa_obras',
        'endereco', 'bairro', 'latitude', 'longitude',
        'observacoes', 'concluida_em', 'created_by', 'legacy_id',
    ];

    protected $casts = [
        'etapa' => EtapaVistoria::class,
        'numero_instalacao' => 'integer',
        'placa_obras' => 'integer',
        'data_relatorio' => 'date',
        'concluida_em' => 'immutable_datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'legacy_id' => 'integer',
    ];

    /* Relacoes */

    public function beneficiario(): BelongsTo
    {
        return $this->belongsTo(CisternaBeneficiario::class, 'beneficiario_id');
    }

    public function itensConferidos(): MorphMany
    {
        return $this->morphMany(CisternaItemConferido::class, 'conferivel');
    }

    public function notificacoes(): MorphMany
    {
        return $this->morphMany(CisternaNotificacao::class, 'notificavel');
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function itemDe(ItemInstalacao $item): ?CisternaItemConferido
    {
        if ($this->relationLoaded('itensConferidos')) {
            return $this->itensConferidos->firstWhere('item', $item);
        }

        return $this->itensConferidos()->where('item', $item->value)->first();
    }

    /**
     * Legado marcava conclusao por `crea_mg` preenchido e diferente de vazio.
     */
    public function estaConcluida(): bool
    {
        return $this->concluida_em !== null;
    }

    /* Scopes */

    public function scopeDaEtapa(Builder $query, EtapaVistoria $etapa): Builder
    {
        return $query->where('etapa', $etapa->value);
    }

    public function scopeConcluidas(Builder $query): Builder
    {
        return $query->whereNotNull('concluida_em');
    }

    /* MediaLibrary */

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('fotos_vistoria');
        $this->addMediaCollection('assinatura_engenheiro')->singleFile();
    }

    protected static function newFactory(): CisternaVistoriaFactory
    {
        return CisternaVistoriaFactory::new();
    }

    /* Trilha de acoes */

    public function moduloNotificacao(): string
    {
        return 'cisterna';
    }

    public function rotuloProtocolo(): string
    {
        $etapa = $this->etapa instanceof EtapaVistoria ? $this->etapa->label() : 'Vistoria';

        return $this->numero_instalacao !== null
            ? $etapa.' — instalacao '.$this->numero_instalacao
            : $etapa.' #'.$this->getKey();
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
        return '/cisternas/vistorias/'.$this->getKey();
    }

    public function campoSituacao(): ?string
    {
        return 'etapa';
    }

    public function rotuloSituacao(): ?string
    {
        return $this->etapa instanceof EtapaVistoria ? $this->etapa->label() : null;
    }

    public function tipoSituacaoNotificacao(): ?string
    {
        return $this->estaConcluida() ? 'success' : 'info';
    }

    /**
     * @return list<string>
     */
    public function camposIgnoradosNaTrilha(): array
    {
        return array_merge($this->camposBaseIgnoradosNaTrilha(), ['legacy_id']);
    }
}
