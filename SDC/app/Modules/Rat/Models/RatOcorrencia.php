<?php

declare(strict_types=1);

namespace App\Modules\Rat\Models;

use App\Modules\Notificacoes\Contracts\Rastreavel;
use App\Modules\Notificacoes\Support\TrilhaDeAcoes;
use App\Modules\Rat\Models\RatAnexo;
use App\Modules\Rat\Models\RatOcorrenciaHistorico;
use App\Modules\Rat\Models\RatOcorrenciaRelato;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RatOcorrencia extends Model implements Rastreavel
{
    use SoftDeletes, HasUuids, TrilhaDeAcoes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'rat_ocorrencias';

    protected $fillable = [
        'numero_bos',
        'sequencial_ano',
        'created_by',
        'status',
        'prazo_edicao',
        'updated_by',
        'ocorrencia_origem_id',
        'historico',
        'anexos',
    ];

    protected $casts = [
        'status'       => 'integer',
        'prazo_edicao' => 'datetime',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
        'deleted_at'   => 'datetime',
        'anexos'       => 'array',
        'historico'    => 'array',
    ];

    protected $appends = ['protocolo'];

    // ─── Relacionamentos ────────────────────────────────────────────────────

    public function ratAnexos(): HasMany
    {
        return $this->hasMany(RatAnexo::class, 'rat_id');
    }

    public function relatosMorph(): HasMany
    {
        return $this->hasMany(RatOcorrenciaRelato::class, 'ocorrencia_id');
    }

    public function relatos_ocorrencia(): HasMany
    {
        return $this->hasMany(RatOcorrenciaRelato::class, 'ocorrencia_id');
    }

    public function historicos(): HasMany
    {
        return $this->hasMany(RatOcorrenciaHistorico::class, 'ocorrencia_id');
    }

    public function ocorrenciaOrigem(): BelongsTo
    {
        return $this->belongsTo(self::class, 'ocorrencia_origem_id');
    }

    public function ocorrenciasFilhas(): HasMany
    {
        return $this->hasMany(self::class, 'ocorrencia_origem_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    public function orgaoEmissor(): BelongsTo
    {
        // Compatibilidade: retorna relação vazia se módulo Compdec não disponível
        try {
            if (class_exists(\App\Modules\Compdec\Models\Orgao::class)) {
                return $this->belongsTo(\App\Modules\Compdec\Models\Orgao::class, 'orgao_emissor_id');
            }
        } catch (\Throwable) {}
        return $this->belongsTo(self::class, 'orgao_emissor_id')->whereRaw('1=0');
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    public function isFinalizada(): bool
    {
        return $this->status === 1;
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status === 1 ? 'Finalizado' : 'Em Andamento';
    }

    public function getProtocoloAttribute(): string
    {
        return $this->numero_bos ?? '';
    }

    // ─── Trilha de acoes (notificacao ao dono) ──────────────────────────────

    public function moduloNotificacao(): string
    {
        return 'rat';
    }

    /**
     * Como o RAT se apresenta ao dono no sino.
     *
     * O fallback e a data de abertura, NUNCA a chave. A notificacao chegava como
     * "A ocorrencia #019eef2b-ee60-7108-8ecd-ba91d21fe9ea foi finalizada" quando
     * numero_bos estava vazio (import de legado), e o usuario lia aquilo como erro do
     * sistema.
     */
    public function rotuloProtocolo(): string
    {
        $numero = trim((string) $this->numero_bos);

        if ($numero !== '') {
            return 'RAT '.$numero;
        }

        return $this->created_at === null
            ? 'RAT sem numero'
            : 'RAT de '.$this->created_at->format('d/m/Y');
    }

    /**
     * @return list<int>
     */
    public function donosNotificacao(): array
    {
        // created_by e string(191) no schema, nao FK inteira: o import de legado pode ter
        // gravado ali algo que nao e id de usuario. Sem id resolvivel nao existe dono, e
        // o protocolo simplesmente nao gera trilha.
        return is_numeric($this->created_by) ? [(int) $this->created_by] : [];
    }

    public function urlNotificacao(): ?string
    {
        return '/rat/'.$this->getKey();
    }

    public function campoSituacao(): ?string
    {
        return 'status';
    }

    public function rotuloSituacao(): ?string
    {
        return $this->status_label;
    }

    /**
     * A consequencia importa mais que o rotulo: finalizar encerra a janela de edicao.
     */
    public function detalheSituacao(): ?string
    {
        return $this->isFinalizada() ? 'e nao aceita mais edicao' : null;
    }

    public function tipoSituacaoNotificacao(): ?string
    {
        return $this->isFinalizada() ? 'success' : 'info';
    }

    /**
     * @return list<string>
     */
    public function camposIgnoradosNaTrilha(): array
    {
        return array_merge($this->camposBaseIgnoradosNaTrilha(), [
            // historico e anexos sao espelhos denormalizados alimentados pelo proprio
            // salvamento; sequencial_ano e prazo_edicao sao controle interno. Nenhum
            // deles e uma alteracao que o dono precise saber.
            'historico',
            'anexos',
            'sequencial_ano',
            'prazo_edicao',
        ]);
    }

    // ─── Helpers internos para acessar relatosMorph respeitando eager-load ──

    private function relatosDe(string $type): \Illuminate\Support\Collection
    {
        if ($this->relationLoaded('relatosMorph')) {
            return $this->relatosMorph->where('conteudo_type', $type)->values();
        }
        return $this->relatosMorph()->where('conteudo_type', $type)->with('conteudo')->get();
    }

    private function primeiroDe(string $type): ?object
    {
        return $this->relatosDe($type)->first()?->conteudo;
    }

    // ─── Atributos computados ────────────────────────────────────────────────

    /** Cache interno para evitar múltiplas queries de dados gerais na mesma requisição. */
    private ?array $cachedDadosGerais = null;

    private function loadDadosGerais(): array
    {
        if ($this->cachedDadosGerais !== null) {
            return $this->cachedDadosGerais;
        }
        $conteudo                = $this->primeiroDe(\App\Modules\Rat\Models\Relatos\RatRelatoDadosGerais::class);
        $this->cachedDadosGerais = $conteudo ? $conteudo->toArray() : [];
        return $this->cachedDadosGerais;
    }

    public function getDadosGeraisAttribute(): array  { return $this->loadDadosGerais(); }
    public function getLocalAttribute(): array         { return $this->loadDadosGerais(); }
    public function getEnderecoAttribute(): array      { return $this->loadDadosGerais(); }
    public function getComunicacaoAttribute(): array   { return $this->loadDadosGerais(); }

    public function getRecursosAttribute(): array
    {
        return $this->relatosDe(\App\Modules\Rat\Models\Relatos\RatRelatoRecurso::class)
            ->map(function ($r) {
                $conteudo = $r->conteudo;
                if (!$conteudo) {
                    return null;
                }
                if (!$conteudo->relationLoaded('agentes')) {
                    $conteudo->load('agentes');
                }
                return $conteudo->toArray();
            })
            ->filter()
            ->values()
            ->toArray();
    }

    public function getEnvolvidosAttribute(): array
    {
        return $this->relatosDe(\App\Modules\Rat\Models\Relatos\RatRelatoEnvolvidos::class)
            ->map(fn ($r) => $r->conteudo?->toArray())
            ->filter()
            ->values()
            ->toArray();
    }

    public function getVistoriaAttribute(): array
    {
        $conteudo = $this->primeiroDe(\App\Modules\Rat\Models\Relatos\RatRelatoVistoria::class);
        return $conteudo ? $conteudo->toArray() : [];
    }

    public function getTemVistoriaAttribute(): bool
    {
        return !empty($this->getVistoriaAttribute());
    }
}
