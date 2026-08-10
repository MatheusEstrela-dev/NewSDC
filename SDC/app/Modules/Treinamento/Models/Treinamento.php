<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Models;

use App\Models\User;
use App\Modules\Notificacoes\Contracts\Rastreavel;
use App\Modules\Notificacoes\Support\TrilhaDeAcoes;
use App\Modules\Treinamento\Enums\CategoriaTreinamento;
use App\Modules\Treinamento\Enums\StatusTreinamento;
use App\Modules\Treinamento\Enums\TipoTreinamento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Treinamento extends Model implements Rastreavel
{
    use SoftDeletes, TrilhaDeAcoes;

    protected $table = 'treinamentos';

    protected $fillable = [
        'titulo',
        'descricao',
        'carga_horaria',
        'categoria',
        'tipo',
        'status',
        'instrutor',
        'local',
        'data_inicio',
        'data_fim',
        'hora_inicio',
        'numero_vagas',
        'percentual_frequencia_minimo',
        'created_by',
        'link_publico_slug',
        'publicado_em',
        'presenca_liberada',
        'presenca_liberada_em',
        'presenca_liberada_por',
        'presenca_autoconfirmavel',
        'finalizado_em',
    ];

    protected $casts = [
        'categoria' => CategoriaTreinamento::class,
        'tipo' => TipoTreinamento::class,
        'status' => StatusTreinamento::class,
        'data_inicio' => 'date',
        'data_fim' => 'date',
        'percentual_frequencia_minimo' => 'decimal:2',
        'presenca_liberada' => 'boolean',
        'presenca_autoconfirmavel' => 'boolean',
        'publicado_em' => 'datetime',
        'presenca_liberada_em' => 'datetime',
        'finalizado_em' => 'datetime',
    ];

    protected $appends = [
        'vagas_disponiveis',
        'total_inscricoes',
    ];

    // Relationships

    public function modulos(): HasMany
    {
        return $this->hasMany(Modulo::class)->orderBy('ordem');
    }

    public function inscricoes(): HasMany
    {
        return $this->hasMany(Inscricao::class);
    }

    public function inscricoesAprovadas(): HasMany
    {
        return $this->hasMany(Inscricao::class)->where('status', 'APROVADA');
    }

    public function certificados(): HasMany
    {
        return $this->hasMany(Certificado::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function presencaLiberadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'presenca_liberada_por');
    }

    // Business Logic

    public function podeReceberInscricao(): bool
    {
        if (!$this->status->podeReceberInscricoes()) {
            return false;
        }

        if ($this->numero_vagas !== null && $this->inscricoesAprovadas()->count() >= $this->numero_vagas) {
            return false;
        }

        return true;
    }

    public function estaCheio(): bool
    {
        if ($this->numero_vagas === null) {
            return false;
        }

        return $this->inscricoesAprovadas()->count() >= $this->numero_vagas;
    }

    public function estaPublicado(): bool
    {
        return $this->link_publico_slug !== null && $this->publicado_em !== null;
    }

    /**
     * Torna o treinamento visivel no catalogo do Portal do Cidadao. Gera o
     * slug publico se ainda nao existir (nao regenera se ja estiver publicado).
     */
    public function publicarNoPortal(): void
    {
        $this->update([
            'link_publico_slug' => $this->link_publico_slug ?? (Str::slug($this->titulo) . '-' . Str::lower(Str::random(6))),
            'publicado_em' => $this->publicado_em ?? now(),
        ]);
    }

    public function liberarPresenca(User $por): void
    {
        $this->update([
            'presenca_liberada' => true,
            'presenca_liberada_em' => now(),
            'presenca_liberada_por' => $por->id,
        ]);
    }

    public function bloquearPresenca(): void
    {
        $this->update(['presenca_liberada' => false]);
    }

    public function finalizar(): void
    {
        $this->update([
            'status' => StatusTreinamento::CONCLUIDO,
            'finalizado_em' => now(),
        ]);
    }

    // Accessors

    public function getVagasDisponiveisAttribute(): ?int
    {
        if ($this->numero_vagas === null) {
            return null;
        }

        $ocupadas = $this->inscricoesAprovadas()->count();
        return max(0, $this->numero_vagas - $ocupadas);
    }

    public function getTotalInscricoesAttribute(): int
    {
        return $this->inscricoes()->count();
    }

    // Scopes

    public function scopePorStatus($query, StatusTreinamento $status)
    {
        return $query->where('status', $status->value);
    }

    public function scopeEmAndamento($query)
    {
        return $query->where('status', StatusTreinamento::EM_ANDAMENTO->value);
    }

    public function scopeConcluidos($query)
    {
        return $query->where('status', StatusTreinamento::CONCLUIDO->value);
    }

    public function scopePublicado($query)
    {
        return $query->whereNotNull('link_publico_slug')->whereNotNull('publicado_em');
    }

    // ─── Trilha de acoes (notificacao ao dono) ──────────────────────────────

    public function moduloNotificacao(): string
    {
        return 'treinamento';
    }

    public function rotuloProtocolo(): string
    {
        $titulo = trim((string) $this->titulo);

        return $titulo === ''
            ? 'Treinamento #'.$this->getKey()
            : 'Treinamento "'.$titulo.'"';
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
        return '/treinamentos/'.$this->getKey();
    }

    public function campoSituacao(): ?string
    {
        return 'status';
    }

    public function rotuloSituacao(): ?string
    {
        return $this->status instanceof StatusTreinamento ? $this->status->getLabel() : null;
    }

    public function tipoSituacaoNotificacao(): ?string
    {
        return match ($this->status) {
            StatusTreinamento::CONCLUIDO => 'success',
            StatusTreinamento::CANCELADO => 'warning',
            default => 'info',
        };
    }
}
