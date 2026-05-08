<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Models;

use App\Modules\Compdec\Enums\StatusValidade;
use App\Modules\Compdec\Enums\TipoAnexo;
use Database\Factories\CompdecAnexoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int            $id
 * @property int            $orgao_id
 * @property TipoAnexo      $tipo
 * @property string         $titulo
 * @property ?string        $descricao
 * @property ?string        $numero
 * @property ?\Carbon\Carbon $data_emissao
 * @property ?\Carbon\Carbon $data_validade
 * @property ?string        $legacy_arquivo
 * @property ?int           $legacy_id
 */
class CompdecAnexo extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use SoftDeletes;

    public const MEDIA_ARQUIVO = 'anexo_arquivo';

    protected $table = 'compdec_anexos';

    protected $fillable = [
        'orgao_id',
        'tipo',
        'titulo',
        'descricao',
        'numero',
        'data_emissao',
        'data_validade',
        'legacy_arquivo',
        'legacy_id',
    ];

    protected $casts = [
        'tipo' => TipoAnexo::class,
        'data_emissao' => 'date',
        'data_validade' => 'date',
        'legacy_id' => 'integer',
    ];

    public function orgao(): BelongsTo
    {
        return $this->belongsTo(Orgao::class, 'orgao_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_ARQUIVO)
            ->useDisk(config('compdec.disk', 'compdec'))
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

    /**
     * Status derivado a partir de data_validade.
     * Janela de alerta vem de config('compdec.anexo_alerta_vencimento_dias').
     */
    protected function statusValidade(): Attribute
    {
        return Attribute::get(function (): StatusValidade {
            $janela = (int) config('compdec.anexo_alerta_vencimento_dias', 30);

            return StatusValidade::fromDataValidade($this->data_validade, $janela);
        });
    }

    public function getArquivoUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl(self::MEDIA_ARQUIVO) ?: null;
    }

    /* Scopes */

    public function scopeDoOrgao(Builder $query, int $orgaoId): Builder
    {
        return $query->where('orgao_id', $orgaoId);
    }

    public function scopeDoTipo(Builder $query, string $tipo): Builder
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Anexos cujo data_validade cai dentro da janela informada.
     */
    public function scopeProximosDoVencimento(Builder $query, int $diasJanela): Builder
    {
        $hoje = now()->startOfDay();
        $limite = $hoje->copy()->addDays($diasJanela);

        return $query->whereNotNull('data_validade')
            ->whereBetween('data_validade', [$hoje, $limite]);
    }

    public function scopeVencidos(Builder $query): Builder
    {
        return $query->whereNotNull('data_validade')
            ->where('data_validade', '<', now()->startOfDay());
    }

    protected static function newFactory(): CompdecAnexoFactory
    {
        return CompdecAnexoFactory::new();
    }
}
