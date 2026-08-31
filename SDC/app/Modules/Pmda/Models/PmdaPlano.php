<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Models;

use App\Models\Municipio;
use App\Modules\Notificacoes\Contracts\Rastreavel;
use App\Modules\Notificacoes\Support\TrilhaDeAcoes;
use App\Modules\Pmda\Enums\PmdaStatus;
use App\Modules\Pmda\Support\DestinatariosPmda;
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
    use TrilhaDeAcoes {
        // Alias porque a classe redeclara o metodo abaixo: metodo da classe vence o
        // do trait, e `parent::` apontaria para Model, que nao o tem.
        reportarAtualizacaoNaTrilha as private reportarAtualizacaoNaTrilhaPadrao;
    }

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
     * Criador MAIS a contraparte do momento: quem precisa saber depende de que
     * lado a bola esta. A regra inteira vive em DestinatariosPmda.
     *
     * @return list<int>
     */
    public function donosNotificacao(): array
    {
        return DestinatariosPmda::paraPlano($this);
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

    /**
     * Como a virada de situacao e ANUNCIADA ao dono, que nem sempre e o rotulo da
     * tela.
     *
     * A devolutiva e o caso que obriga a distincao: no banco ela volta o plano para
     * RASCUNHO, entao o rotulo cru dizia "Plano PMDA em edicao" -- o municipio lia
     * que o proprio plano mudou de fase, e nao que a CEDEC devolveu pedindo
     * correcao. O que muda o que ele precisa fazer e a devolucao, nao o rascunho.
     */
    public function rotuloSituacao(): ?string
    {
        if ($this->pedido_altera && $this->status === PmdaStatus::RASCUNHO) {
            return 'Devolvido para alteração';
        }

        // A frase do card e "<protocolo> foi <situacao> por <autor>", entao o rotulo
        // precisa ser participio. "Em Analise" e rotulo de coluna e saia como "foi em
        // analise por Fulano".
        if ($this->status === PmdaStatus::EM_ANALISE) {
            return 'Enviado para análise';
        }

        return $this->status instanceof PmdaStatus
            ? $this->status->getLabel()
            : (($this->status === null || $this->status === '') ? null : (string) $this->status);
    }

    /**
     * Complemento da frase da notificacao. O motivo e o unico dado que evita o
     * municipio abrir o plano so para descobrir o que corrigir.
     */
    public function detalheSituacao(): ?string
    {
        $motivo = trim((string) $this->motivo_analise);

        if ($motivo === '') {
            return null;
        }

        return in_array($this->status, [PmdaStatus::RASCUNHO, PmdaStatus::ARQUIVADO], true)
            ? 'Motivo: '.$motivo
            : null;
    }

    /**
     * Gravidade do card. Devolucao e arquivamento pedem acao ou encerram o pedido;
     * sair como "info" junto de um aviso qualquer faz o usuario passar batido.
     */
    public function tipoSituacaoNotificacao(): ?string
    {
        return match (true) {
            $this->status === PmdaStatus::ARQUIVADO                     => 'error',
            $this->pedido_altera && $this->status === PmdaStatus::RASCUNHO => 'warning',
            $this->status === PmdaStatus::APROVADO                      => 'success',
            default                                                     => null,
        };
    }

    /**
     * Silencia a virada automatica RASCUNHO <-> COMPLETO.
     *
     * PmdaService::recalcularStatus() reavalia a situacao a cada comunidade ou
     * representante salvo; sem isso o dono recebia "Plano PMDA completo" e
     * "Plano PMDA em edicao" alternando durante o proprio preenchimento -- aviso
     * sobre algo que ele esta fazendo naquele instante e olhando na tela.
     *
     * A derivacao se distingue por tocar SO a coluna status: todo tramite de
     * verdade (enviar, aprovar, arquivar, devolver) escreve tambem data e
     * responsavel no mesmo update.
     */
    protected function reportarAtualizacaoNaTrilha(): void
    {
        $alterados = array_values(array_diff(
            array_keys($this->getChanges()),
            $this->camposIgnoradosNaTrilha(),
        ));

        $derivacao = $alterados === ['status']
            && in_array($this->status, [PmdaStatus::RASCUNHO, PmdaStatus::COMPLETO], true);

        if ($derivacao) {
            return;
        }

        $this->reportarAtualizacaoNaTrilhaPadrao();
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
