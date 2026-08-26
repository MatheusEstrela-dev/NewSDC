<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Models;

use App\Models\Municipio;
use App\Modules\Notificacoes\Contracts\Rastreavel;
use App\Modules\Notificacoes\Support\TrilhaDeAcoes;
use App\Modules\Pmda\Enums\PmdaStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class PmdaPlano extends Model implements HasMedia, Rastreavel
{
    use InteractsWithMedia;
    use SoftDeletes;
    use TrilhaDeAcoes;

    /** Colecoes de anexos (Etapa 7). */
    public const MEDIA_TERMO = 'termo';
    public const MEDIA_OFICIO = 'oficio';

    protected $table = 'pmda_planos';

    protected $fillable = [
        'protocolo', 'municipio_id', 'status', 'data', 'acoes', 'qtd_caminhao',
        'pop_at_municipio', 'pedido_altera', 'alterar_com', 'resp_homolog', 'dt_analise',
        'dt_ultima_alteracao', 'data_aprov', 'resp_estado', 'dt_estado', 'motivo_analise',
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

    /** Log append-only das transicoes do plano (serie historica). */
    public function eventos(): HasMany
    {
        return $this->hasMany(PmdaPlanoEvento::class, 'pmda_plano_id');
    }

    /** Solicitacoes de inclusao de comunidade abertas a partir deste plano. */
    public function solicitacoesComunidade(): HasMany
    {
        return $this->hasMany(ComunidadeSolicitacao::class, 'pmda_plano_id');
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

    // ─── Trilha de acoes (notificacao ao dono) ──────────────────────────────

    public function moduloNotificacao(): string
    {
        return 'pmda';
    }

    public function rotuloProtocolo(): string
    {
        $protocolo = trim((string) $this->protocolo);

        return $protocolo === ''
            ? 'Plano PMDA de '.($this->created_at?->format('d/m/Y') ?? 'data nao informada')
            : 'Plano PMDA '.$protocolo;
    }

    /**
     * @return list<int>
     */
    public function donosNotificacao(): array
    {
        return $this->created_by === null ? [] : [(int) $this->created_by];
    }

    /**
     * O modulo nao tem rota de exibicao do plano, apenas index, edit e continuar. O card
     * aponta para a lista de propósito: /pmda/planos/{id}/edit exige a permissao
     * pmda.planos.edit, que o dono do plano pode nao ter -- e um botao que devolve 403 e
     * pior do que um que abre a lista.
     */
    public function urlNotificacao(): ?string
    {
        return '/pmda/planos';
    }

    public function campoSituacao(): ?string
    {
        return 'status';
    }

    public function rotuloSituacao(): ?string
    {
        return $this->status instanceof PmdaStatus
            ? $this->status->getLabel()
            : (($this->status === null || $this->status === '') ? null : (string) $this->status);
    }

    /**
     * @return list<string>
     */
    public function camposIgnoradosNaTrilha(): array
    {
        return array_merge($this->camposBaseIgnoradosNaTrilha(), [
            // Espelho de busca/contagem alimentado pelo proprio salvamento.
            'acoes',
        ]);
    }
}
