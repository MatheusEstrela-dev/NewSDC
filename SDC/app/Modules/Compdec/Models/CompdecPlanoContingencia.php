<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Models;

use App\Models\User;
use Database\Factories\CompdecPlanoContingenciaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int     $id
 * @property int     $orgao_id
 * @property string  $versao
 * @property ?int    $tamanho_bytes
 * @property ?string $observacoes
 * @property bool    $ativo
 * @property ?\Carbon\Carbon $aprovado_em
 * @property ?int    $aprovado_por
 * @property ?string $legacy_arquivo
 * @property ?int    $legacy_id
 */
class CompdecPlanoContingencia extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use SoftDeletes;

    public const MEDIA_ARQUIVO = 'plano_arquivo';

    protected $table = 'compdec_planos_contingencia';

    protected $fillable = [
        'orgao_id',
        'versao',
        'tamanho_bytes',
        'observacoes',
        'ativo',
        'aprovado_em',
        'aprovado_por',
        'legacy_arquivo',
        'legacy_id',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'aprovado_em' => 'datetime',
        'tamanho_bytes' => 'integer',
        'aprovado_por' => 'integer',
        'legacy_id' => 'integer',
    ];

    public function orgao(): BelongsTo
    {
        return $this->belongsTo(Orgao::class, 'orgao_id');
    }

    public function aprovador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprovado_por');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_ARQUIVO)
            ->singleFile()
            ->acceptsMimeTypes([
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.oasis.opendocument.text',
            ]);
    }

    public function getArquivoUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl(self::MEDIA_ARQUIVO) ?: null;
    }

    public function getEstaAprovadoAttribute(): bool
    {
        return $this->aprovado_em !== null && $this->aprovado_por !== null;
    }

    /* Scopes */

    public function scopeDoOrgao(Builder $query, int $orgaoId): Builder
    {
        return $query->where('orgao_id', $orgaoId);
    }

    public function scopeAtivo(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    public function scopeAprovados(Builder $query): Builder
    {
        return $query->whereNotNull('aprovado_em');
    }

    protected static function newFactory(): CompdecPlanoContingenciaFactory
    {
        return CompdecPlanoContingenciaFactory::new();
    }
}
