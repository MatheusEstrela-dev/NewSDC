<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Models;

use App\Models\Municipio;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Database\Factories\PrefeituraFactory;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Prefeitura extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use SoftDeletes;

    public const MEDIA_FOTO_PREFEITO = 'foto_prefeito';

    protected $table = 'compdec_prefeituras';

    protected $fillable = [
        'municipio_id',
        'prefeito_nome',
        'prefeito_telefone',
        'prefeito_celular',
        'prefeito_email',
        'endereco',
        'bairro',
        'cep',
        'latitude',
        'longitude',
        'inss_tem_cobranca',
        'inss_aliquota',
        'inss_lei_cobranca',
        'inss_responsavel',
        'legacy_id',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'inss_tem_cobranca' => 'boolean',
        'inss_aliquota' => 'decimal:2',
        'legacy_id' => 'integer',
    ];

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_FOTO_PREFEITO)
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Contain, 200, 200)
            ->nonQueued();
    }

    protected function fotoPrefeitoUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->getFirstMedia(self::MEDIA_FOTO_PREFEITO)?->getUrl());
    }

    protected static function newFactory(): PrefeituraFactory
    {
        return PrefeituraFactory::new();
    }
}
