<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Models;

use App\Models\Municipio;
use App\Modules\Pmda\Enums\PmdaStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class PmdaPlano extends Model implements HasMedia
{
    use InteractsWithMedia;
    use SoftDeletes;

    /** Colecoes de anexos (Etapa 7). */
    public const MEDIA_TERMO = 'termo';
    public const MEDIA_OFICIO = 'oficio';

    protected $table = 'pmda_planos';

    protected $fillable = [
        'protocolo', 'municipio_id', 'status', 'data', 'acoes', 'qtd_caminhao',
        'pop_at_municipio', 'pedido_altera', 'alterar_com', 'resp_homolog', 'dt_analise',
        'dt_ultima_alteracao', 'data_aprov', 'resp_estado', 'dt_estado',
        'motivo', 'acao_decreto_se', 'acao_caminhao_pipa', 'acao_cestas_basicas', 'justificativa_apoio',
        'cobra_iss', 'num_lei_iss', 'aliquota_iss', 'resp_cob_iss',
        'nome_prefeito', 'tel_prefeitura', 'tel_prefeito', 'cel_prefeito', 'endereco',
        'bairro', 'cep', 'email_prefeitura', 'populacao', 'pop_rural', 'area',
        'compdec_coordenador', 'compdec_decreto', 'compdec_lei', 'compdec_tel', 'compdec_email',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'status'              => PmdaStatus::class,
        'data'                => 'datetime',
        'dt_analise'          => 'datetime',
        'data_aprov'          => 'datetime',
        'dt_estado'           => 'datetime',
        'dt_ultima_alteracao' => 'datetime',
        'pedido_altera'       => 'boolean',
        'alterar_com'         => 'boolean',
        'acao_decreto_se'     => 'boolean',
        'acao_caminhao_pipa'  => 'boolean',
        'acao_cestas_basicas' => 'boolean',
        'cobra_iss'           => 'boolean',
        'aliquota_iss'        => 'decimal:2',
        'populacao'           => 'integer',
        'pop_rural'           => 'integer',
        'area'                => 'decimal:2',
    ];

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    public function comunidades(): HasMany
    {
        return $this->hasMany(PmdaComunidade::class, 'pmda_plano_id');
    }

    public function compdecMembros(): HasMany
    {
        return $this->hasMany(PmdaCompdecMembro::class, 'pmda_plano_id');
    }

    public function pontos(): BelongsToMany
    {
        return $this->belongsToMany(PmdaPonto::class, 'pmda_plano_ponto', 'pmda_plano_id', 'ponto_id')
            ->withPivot('situacao')
            ->withTimestamps();
    }

    public function registerMediaCollections(): void
    {
        foreach ([self::MEDIA_TERMO, self::MEDIA_OFICIO] as $colecao) {
            $this->addMediaCollection($colecao)
                ->singleFile()
                ->acceptsMimeTypes(['application/pdf']);
        }
    }
}
