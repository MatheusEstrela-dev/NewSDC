<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Models;

use App\Models\User;
use App\Modules\Notificacoes\Contracts\Rastreavel;
use App\Modules\Notificacoes\Support\TrilhaDeAcoes;
use App\Modules\Treinamento\Enums\StatusTreinamento;
use App\Modules\Treinamento\Enums\TipoTreinamento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Treinamento extends Model implements Rastreavel
{
    use SoftDeletes, TrilhaDeAcoes;

    protected $table = 'treinamentos';

    protected $fillable = [
        'titulo',
        'descricao',
        'carga_horaria',
        'tipo',
        'status',
        'instrutor',
        'local',
        'data_inicio',
        'data_fim',
        'numero_vagas',
        'percentual_frequencia_minimo',
        'created_by',
    ];

    protected $casts = [
        'tipo' => TipoTreinamento::class,
        'status' => StatusTreinamento::class,
        'data_inicio' => 'date',
        'data_fim' => 'date',
        'percentual_frequencia_minimo' => 'decimal:2',
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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
