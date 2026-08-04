<?php

declare(strict_types=1);

namespace App\Modules\Pae\Models;

use App\Modules\Notificacoes\Contracts\Rastreavel;
use App\Modules\Notificacoes\Support\TrilhaDeAcoes;
use App\Modules\Pae\Enums\PaeProtocoloStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class PaeProtocolo extends Model implements Rastreavel
{
    use HasFactory, SoftDeletes, TrilhaDeAcoes;

    protected $table = 'pae_protocolos';

    protected $fillable = [
        'sigibar',
        'num_protocolo',
        'status',
        'situacao',
        'analista_atual_id',
        'user_id',
        'created_by',
        'updated_by',
        'pae_empnto_id',
        'protocolo_origem_id',
        'arquivado',
        'sei_numero',
        'dt_entrada',
        'limite_analise',
        'ccpae_venc',
        'ccpae',
        'obs',
        'empnto_search',
    ];

    protected $casts = [
        'status' => PaeProtocoloStatus::class,
        'arquivado' => 'boolean',
        'dt_entrada' => 'date',
        'limite_analise' => 'date',
        'ccpae_venc' => 'date',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\Pae\PaeProtocoloFactory::new();
    }

    public function analistaAtual(): BelongsTo
    {
        return $this->belongsTo(User::class, 'analista_atual_id');
    }

    public function empreendimento(): BelongsTo
    {
        return $this->belongsTo(PaeEmpnto::class, 'pae_empnto_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function origem(): BelongsTo
    {
        return $this->belongsTo(self::class, 'protocolo_origem_id');
    }

    public function versoes(): HasMany
    {
        return $this->hasMany(self::class, 'protocolo_origem_id');
    }

    public function tramitacoes(): HasMany
    {
        return $this->hasMany(PaeTramitacao::class, 'protocolo_id')->orderBy('dt_status', 'desc');
    }

    public function timeline(): HasMany
    {
        return $this->hasMany(PaeTimeline::class, 'protocolo_id')->orderBy('created_at', 'asc');
    }

    public function validarTransicaoStatus(PaeProtocoloStatus $novo): bool
    {
        return $this->status->canTransitionTo($novo);
    }

    public function scopeAtivo($query)
    {
        return $query->where('arquivado', false);
    }

    public function scopePorAnalista($query, int $analistaId)
    {
        return $query->where('analista_atual_id', $analistaId);
    }

    public function scopePorStatus($query, PaeProtocoloStatus $status)
    {
        return $query->where('status', $status->value);
    }

    public function getAnaliseStatusAttribute(): ?string
    {
        $concluida = in_array($this->status, [
            PaeProtocoloStatus::APROVADO,
            PaeProtocoloStatus::REPROVADO,
            PaeProtocoloStatus::CCPAE,
            PaeProtocoloStatus::ATIVO_3_ANOS,
            PaeProtocoloStatus::REVOGADO,
        ], true);

        if ($concluida) {
            return 'concluida';
        }

        $emAndamento = $this->analista_atual_id
            && in_array($this->status, [
                PaeProtocoloStatus::NOTIFICACAO,
                PaeProtocoloStatus::ANALISE,
            ], true);

        return $emAndamento ? 'em_andamento' : null;
    }

    // ─── Trilha de acoes (notificacao ao dono) ──────────────────────────────

    public function moduloNotificacao(): string
    {
        return 'pae';
    }

    public function rotuloProtocolo(): string
    {
        $numero = trim((string) $this->num_protocolo);

        return $numero === ''
            ? 'Protocolo PAE de '.($this->created_at?->format('d/m/Y') ?? 'data nao informada')
            : 'Protocolo PAE '.$numero;
    }

    /**
     * @return list<int>
     */
    public function donosNotificacao(): array
    {
        // created_by e user_id costumam apontar para a mesma pessoa; o analista atual e
        // o responsavel do momento. O servico deduplica e remove quem fez a acao.
        return array_values(array_filter(array_map(
            fn ($id): ?int => $id === null ? null : (int) $id,
            [$this->created_by, $this->user_id, $this->analista_atual_id],
        ), fn (?int $id): bool => $id !== null));
    }

    /**
     * A rota pae.index e GET /pae/protocolo e recebe o protocolo pela query. Nao existe
     * GET /pae/protocolo/{id}: um link nesse formato cai em 404 e o botao do card fica
     * morto. Mesma URL que o PaeAvisoInbox ja usa.
     */
    public function urlNotificacao(): ?string
    {
        return '/pae/protocolo?protocolo_id='.$this->getKey();
    }

    /**
     * Null de proposito: mudanca de status do protocolo PAE ja e avisada pelos gatilhos
     * do modulo via PaeAvisoInbox. Declarar 'status' aqui duplicaria o card.
     */
    public function campoSituacao(): ?string
    {
        return null;
    }

    /**
     * @return list<string>
     */
    public function camposIgnoradosNaTrilha(): array
    {
        return array_merge($this->camposBaseIgnoradosNaTrilha(), [
            // Status e atribuicao tem aviso proprio no modulo (ver campoSituacao).
            'status',
            'analista_atual_id',
            // Coluna de apoio a busca, alimentada pelo sistema.
            'empnto_search',
        ]);
    }
}
