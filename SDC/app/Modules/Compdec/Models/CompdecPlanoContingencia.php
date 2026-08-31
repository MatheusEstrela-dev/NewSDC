<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Models;

use App\Models\User;
use App\Modules\Notificacoes\Contracts\Rastreavel;
use App\Modules\Notificacoes\Support\TrilhaDeAcoes;
use Database\Factories\CompdecPlanoContingenciaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
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
 * @property ?\Carbon\Carbon $enviado_em
 * @property ?string $legacy_arquivo
 * @property ?int    $legacy_id
 * @property ?int    $legacy_municipio_id
 */
class CompdecPlanoContingencia extends Model implements HasMedia, Rastreavel
{
    use HasFactory;
    use InteractsWithMedia;
    use SoftDeletes;
    use TrilhaDeAcoes;

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
        'enviado_em',
        'legacy_arquivo',
        'legacy_id',
        'legacy_municipio_id',
        'created_by',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'aprovado_em' => 'datetime',
        'enviado_em' => 'datetime',
        'tamanho_bytes' => 'integer',
        'aprovado_por' => 'integer',
        'legacy_id' => 'integer',
        'legacy_municipio_id' => 'integer',
    ];

    public function orgao(): BelongsTo
    {
        return $this->belongsTo(Orgao::class, 'orgao_id');
    }

    public function aprovador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprovado_por');
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Mesmo padrao de Compdec\Orgao: o dono e quem cadastrou, gravado na
     * criacao. Nao sobrescreve valor que ja veio (ETL, seeder).
     */
    protected static function booted(): void
    {
        static::creating(function (self $plano): void {
            if ($plano->created_by === null && Auth::id() !== null) {
                $plano->created_by = Auth::id();
            }
        });
    }

    // ─── Trilha de acoes (notificacao ao dono) ──────────────────────────────

    public function moduloNotificacao(): string
    {
        return 'plancon';
    }

    public function rotuloProtocolo(): string
    {
        $municipio = $this->orgao?->municipio?->nome;

        return $municipio === null
            ? 'Plano de contingencia '.$this->versao
            : "Plano de contingencia {$this->versao} de {$municipio}";
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
        return $this->orgao_id === null
            ? null
            : "/compdec/orgaos/{$this->orgao_id}/planos/{$this->getKey()}";
    }

    public function campoSituacao(): ?string
    {
        return 'ativo';
    }

    public function rotuloSituacao(): ?string
    {
        return $this->ativo ? 'Ativo' : 'Inativo';
    }

    /**
     * Plano deixar de ser o ativo do orgao e informacao, nao problema: o que
     * troca o ativo e a ativacao de outra versao, avisada no proprio registro.
     */
    public function tipoSituacaoNotificacao(): ?string
    {
        return $this->ativo ? 'success' : 'info';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_ARQUIVO)
            ->singleFile()
            ->acceptsFile(fn ($file): bool => in_array($file->mimeType, [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.oasis.opendocument.text',
            ], true) || (
                $file->mimeType === 'application/x-empty'
                && in_array(mb_strtolower(pathinfo($file->name, PATHINFO_EXTENSION)), ['pdf', 'doc', 'docx', 'odt'], true)
            ));
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
