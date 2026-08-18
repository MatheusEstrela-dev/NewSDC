<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Models;

use App\Models\Municipio;
use App\Models\User;
use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Enums\SituacaoAnalise;
use App\Modules\Cisterna\Enums\SituacaoObra;
use App\Modules\Notificacoes\Contracts\Rastreavel;
use App\Modules\Notificacoes\Support\TrilhaDeAcoes;
use Database\Factories\Cisterna\CisternaBeneficiarioFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Beneficiario do Projeto Cisterna. Porte de `sinc_cisterna` do legado `sdc`,
 * onde as 54 colunas eram todas varchar(150) — datas, moeda, medidas e
 * booleanos inclusive.
 *
 * @property int $id
 * @property string $cpf
 * @property string $nome
 * @property SituacaoAnalise $situacao_analise
 * @property SituacaoObra $situacao_obra
 * @property ?int $ranqueamento_ordem
 * @property ?int $legacy_id
 */
class CisternaBeneficiario extends Model implements HasMedia, Rastreavel
{
    use HasFactory;
    use InteractsWithMedia;
    use SoftDeletes;
    use TrilhaDeAcoes;

    protected $table = 'cisterna_beneficiarios';

    protected $fillable = [
        'cpf', 'nome', 'telefone', 'data_nascimento', 'cadastro_unico',
        'municipio_id', 'comunidade_id', 'endereco', 'latitude', 'longitude',
        'ordem_servico_id',
        'situacao_analise', 'situacao_analise_obs', 'situacao_obra',
        'ranqueamento_ordem',
        'qtd_pessoas', 'renda', 'renda_per_capita',
        'possui_deficiencia', 'possui_crianca', 'data_nascimento_crianca',
        'possui_idoso', 'chefiada_mulher',
        'tipo_moradia', 'tipo_moradia_outro',
        'comprimento_telhado', 'largura_telhado', 'area_telhado', 'comprimento_testada',
        'num_caidas_telhado', 'cobertura_telhado', 'cobertura_outro',
        'possui_fogao_lenha', 'medida_telhado_area_fogao', 'testada_disp_parte_fogao',
        'atendido_por_pipa',
        'agente_nome', 'agente_cpf', 'engenheiro_nome', 'engenheiro_crea',
        'observacoes', 'created_by', 'legacy_id',
    ];

    protected $casts = [
        'situacao_analise' => SituacaoAnalise::class,
        'situacao_obra' => SituacaoObra::class,
        'data_nascimento' => 'date',
        'data_nascimento_crianca' => 'date',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'renda' => 'decimal:2',
        'renda_per_capita' => 'decimal:2',
        'comprimento_telhado' => 'decimal:2',
        'largura_telhado' => 'decimal:2',
        'area_telhado' => 'decimal:2',
        'comprimento_testada' => 'decimal:2',
        'medida_telhado_area_fogao' => 'decimal:2',
        'testada_disp_parte_fogao' => 'decimal:2',
        'qtd_pessoas' => 'integer',
        'num_caidas_telhado' => 'integer',
        'ranqueamento_ordem' => 'integer',
        'possui_deficiencia' => 'boolean',
        'possui_crianca' => 'boolean',
        'possui_idoso' => 'boolean',
        'chefiada_mulher' => 'boolean',
        'possui_fogao_lenha' => 'boolean',
        'atendido_por_pipa' => 'boolean',
        'legacy_id' => 'integer',
    ];

    /* Relacoes */

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    public function comunidade(): BelongsTo
    {
        return $this->belongsTo(CisternaComunidade::class, 'comunidade_id');
    }

    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(CisternaOrdemServico::class, 'ordem_servico_id');
    }

    public function vistorias(): HasMany
    {
        return $this->hasMany(CisternaVistoria::class, 'beneficiario_id');
    }

    public function atendimentosPipa(): HasMany
    {
        return $this->hasMany(CisternaAtendimentoPipa::class, 'beneficiario_id');
    }

    public function notificacoes(): MorphMany
    {
        return $this->morphMany(CisternaNotificacao::class, 'notificavel');
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Substitui os tres whereHas aninhados do legado
     * (CisternaController.php:441-457) por lookup direto no par unico
     * (beneficiario_id, etapa).
     */
    public function vistoriaDaEtapa(EtapaVistoria $etapa): ?CisternaVistoria
    {
        if ($this->relationLoaded('vistorias')) {
            return $this->vistorias->firstWhere('etapa', $etapa);
        }

        return $this->vistorias()->where('etapa', $etapa->value)->first();
    }

    /* Scopes */

    public function scopeDoMunicipio(Builder $query, int $municipioId): Builder
    {
        return $query->where('municipio_id', $municipioId);
    }

    /**
     * @param  array<int, string>  $situacoes
     */
    public function scopeComSituacaoObra(Builder $query, array $situacoes): Builder
    {
        return $query->whereIn('situacao_obra', $situacoes);
    }

    /**
     * Apoiado no indice GIN pg_trgm de `nome`, em vez do like '%x%' do legado.
     */
    public function scopeBuscarPorNome(Builder $query, ?string $termo): Builder
    {
        if ($termo === null || trim($termo) === '') {
            return $query;
        }

        return $query->where('nome', 'ilike', '%'.trim($termo).'%');
    }

    /**
     * Apoiado no indice parcial de ranqueamento_ordem.
     */
    public function scopeRanqueados(Builder $query): Builder
    {
        return $query->whereNotNull('ranqueamento_ordem')->orderBy('ranqueamento_ordem');
    }

    /* MediaLibrary */

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('fotos_imovel');
        $this->addMediaCollection('comprovantes');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(320)->height(320)->nonQueued();
    }

    /* Boot */

    protected static function newFactory(): CisternaBeneficiarioFactory
    {
        return CisternaBeneficiarioFactory::new();
    }

    /**
     * Mesmo padrao de Decretacoes\Processo: o dono e quem cadastrou. Sem isso
     * a coluna nao se preenche e a trilha nao acha destinatario. Nao
     * sobrescreve valor que ja veio (refino do ETL, seeder).
     */
    protected static function booted(): void
    {
        static::creating(function (self $beneficiario): void {
            if ($beneficiario->created_by === null && Auth::id() !== null) {
                $beneficiario->created_by = Auth::id();
            }
        });
    }

    /* Trilha de acoes */

    public function moduloNotificacao(): string
    {
        return 'cisterna';
    }

    public function rotuloProtocolo(): string
    {
        $nome = trim($this->nome);

        return $nome !== '' ? 'Cisterna de '.$nome : 'Cisterna #'.$this->getKey();
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
        return '/cisternas/beneficiarios/'.$this->getKey();
    }

    public function campoSituacao(): ?string
    {
        return 'situacao_analise';
    }

    public function rotuloSituacao(): ?string
    {
        return $this->situacao_analise instanceof SituacaoAnalise
            ? $this->situacao_analise->label()
            : null;
    }

    public function tipoSituacaoNotificacao(): ?string
    {
        return match ($this->situacao_analise) {
            SituacaoAnalise::APROVADO => 'success',
            SituacaoAnalise::REPROVADO, SituacaoAnalise::DUPLICADO => 'danger',
            SituacaoAnalise::RESSALVA => 'warning',
            default => 'info',
        };
    }

    /**
     * @return list<string>
     */
    public function camposIgnoradosNaTrilha(): array
    {
        return array_merge($this->camposBaseIgnoradosNaTrilha(), [
            // Chave de idempotencia do ETL, nao dado do cadastro.
            'legacy_id',
        ]);
    }
}
