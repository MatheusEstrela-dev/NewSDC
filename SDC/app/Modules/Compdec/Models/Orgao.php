<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Models;

use App\Models\User;
use App\Modules\Compdec\Enums\StatusOrgao;
use App\Modules\Compdec\Enums\TipoOrgao;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Municipio;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Database\Factories\OrgaoFactory;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Orgao extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use SoftDeletes;

    public const MEDIA_FOTO_COORDENADOR = 'foto_coordenador';

    protected $table = 'compdec_orgaos';

    protected $fillable = [
        // Identificacao
        'codigo',
        'nome',
        'tipo',
        'municipio_id',
        'orgao_superior_id',
        'status',
        // Contato
        'email',
        'email_secundario',
        'email_terciario',
        'telefone',
        'telefone_secundario',
        'fax',
        'endereco',
        // Responsavel
        'responsavel_nome',
        'responsavel_cpf',
        'responsavel_telefone',
        'responsavel_email',
        // Geo
        'latitude',
        'longitude',
        // Atos legais
        'lei_criacao_numero',
        'lei_criacao_data',
        'decreto_numero',
        'decreto_data',
        'portaria_numero',
        'portaria_data',
        // Quantitativos
        'qtd_efetivo',
        'qtd_nupdec',
        // Capacidades estruturais
        'tem_sede_propria',
        'tem_viatura',
        'tem_plano_contingencia',
        'tem_mapeamento_risco',
        'tem_simulado',
        'tem_cartao_pdc',
        // JSONB
        'abrangencia',
        'metadata',
        // Rastreabilidade
        'legacy_id',
    ];

    protected $casts = [
        'tipo' => TipoOrgao::class,
        'status' => StatusOrgao::class,
        'abrangencia' => 'array',
        'metadata' => 'array',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'lei_criacao_data' => 'date',
        'decreto_data' => 'date',
        'portaria_data' => 'date',
        'qtd_efetivo' => 'integer',
        'qtd_nupdec' => 'integer',
        'tem_sede_propria' => 'boolean',
        'tem_viatura' => 'boolean',
        'tem_plano_contingencia' => 'boolean',
        'tem_mapeamento_risco' => 'boolean',
        'tem_simulado' => 'boolean',
        'tem_cartao_pdc' => 'boolean',
        'legacy_id' => 'integer',
    ];

    /**
     * Acesso direto ao bloco metadata->capacidades como array.
     */
    protected function capacidades(): Attribute
    {
        return Attribute::make(
            get: fn (): array => $this->metadata['capacidades'] ?? [],
            set: function (?array $value): array {
                $metadata = $this->metadata ?? [];
                $metadata['capacidades'] = $value ?? [];

                return ['metadata' => json_encode($metadata)];
            },
        );
    }

    public function orgaoSuperior(): BelongsTo
    {
        return $this->belongsTo(self::class, 'orgao_superior_id');
    }

    public function orgaosSubordinados(): HasMany
    {
        return $this->hasMany(self::class, 'orgao_superior_id');
    }

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'compdec_orgao_user', 'orgao_id', 'user_id')
            ->withPivot(['funcao', 'is_principal'])
            ->withTimestamps();
    }

    public function prefeitura(): HasOne
    {
        return $this->hasOne(Prefeitura::class, 'municipio_id', 'municipio_id');
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    public function equipes(): HasMany
    {
        return $this->hasMany(CompdecEquipe::class, 'orgao_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_FOTO_COORDENADOR)
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Contain, 200, 200)
            ->nonQueued();
    }

    /* Scopes */

    public function scopeCedec(Builder $query): Builder
    {
        return $query->where('tipo', TipoOrgao::CEDEC->value);
    }

    public function scopeRedec(Builder $query): Builder
    {
        return $query->where('tipo', TipoOrgao::REDEC->value);
    }

    public function scopeCompdec(Builder $query): Builder
    {
        return $query->where('tipo', TipoOrgao::COMPDEC->value);
    }

    public function scopeAtivo(Builder $query): Builder
    {
        return $query->where('status', StatusOrgao::ATIVO->value);
    }

    public function scopeComHierarquia(Builder $query): Builder
    {
        return $query->with(['orgaoSuperior', 'orgaosSubordinados']);
    }

    public function scopeBuscarPorTermo(Builder $query, ?string $termo): Builder
    {
        if (! $termo) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($termo): void {
            $q->where('codigo', 'ilike', "%{$termo}%")
                ->orWhere('nome', 'ilike', "%{$termo}%");
        });
    }

    /* Helpers */

    public function isCedec(): bool
    {
        return $this->tipo === TipoOrgao::CEDEC;
    }

    public function isRedec(): bool
    {
        return $this->tipo === TipoOrgao::REDEC;
    }

    public function isCompdec(): bool
    {
        return $this->tipo === TipoOrgao::COMPDEC;
    }

    public function isAtivo(): bool
    {
        return $this->status === StatusOrgao::ATIVO;
    }

    public function getCaminhoHierarquico(): array
    {
        $caminho = [$this];
        $current = $this;

        while ($current->orgaoSuperior) {
            $current = $current->orgaoSuperior;
            array_unshift($caminho, $current);
        }

        return $caminho;
    }

    public function getNivelHierarquico(): int
    {
        return match ($this->tipo) {
            TipoOrgao::CEDEC => 0,
            TipoOrgao::REDEC => 1,
            TipoOrgao::COMPDEC => 2,
            default => 3,
        };
    }

    public function getFotoCoordenadorUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia(self::MEDIA_FOTO_COORDENADOR);

        return $media?->getUrl();
    }

    protected static function newFactory(): OrgaoFactory
    {
        return OrgaoFactory::new();
    }
}
