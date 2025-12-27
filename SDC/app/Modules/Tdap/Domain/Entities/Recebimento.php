<?php

namespace App\Modules\Tdap\Domain\Entities;

use App\Modules\Tdap\Domain\ValueObjects\RecebimentoStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Representa o recebimento de materiais (TDPA Modal)
 */
class Recebimento extends Model
{
    use SoftDeletes;

    protected $table = 'tdap_recebimentos';

    protected $fillable = [
        'numero_recebimento',
        'ordem_compra_id',
        'nota_fiscal',
        'placa_veiculo',
        'transportadora',
        'motorista_nome',
        'motorista_documento',
        'doca_descarga',
        'data_chegada',
        'data_inicio_conferencia',
        'data_fim_conferencia',
        'conferido_por',
        'aprovado_por',
        'status',
        'observacoes',
    ];

    protected $casts = [
        'data_chegada' => 'datetime',
        'data_inicio_conferencia' => 'datetime',
        'data_fim_conferencia' => 'datetime',
        'status' => RecebimentoStatus::class,
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Recebimento $recebimento) {
            if (empty($recebimento->numero_recebimento)) {
                $recebimento->numero_recebimento = $recebimento->gerarNumeroRecebimento();
            }
        });
    }

    // Business Logic Methods

    /**
     * Gera número único de recebimento
     */
    public function gerarNumeroRecebimento(): string
    {
        $ano = date('Y');
        $mes = date('m');
        $random = strtoupper(Str::random(6));

        return "REC-{$ano}{$mes}-{$random}";
    }

    /**
     * Inicia a conferência do recebimento
     * Nota: O repositório deve chamar save() após esta operação
     */
    public function iniciarConferencia(int $userId): void
    {
        if (!$this->status->canTransitionTo(RecebimentoStatus::EM_CONFERENCIA)) {
            throw new \DomainException("Não é possível iniciar conferência no status atual: {$this->status->getLabel()}");
        }

        $this->status = RecebimentoStatus::EM_CONFERENCIA;
        $this->data_inicio_conferencia = now();
        $this->conferido_por = $userId;
    }

    /**
     * Finaliza a conferência
     * Nota: O repositório deve chamar save() após esta operação
     */
    public function finalizarConferencia(): void
    {
        if (!$this->status->canTransitionTo(RecebimentoStatus::CONFERIDO)) {
            throw new \DomainException("Não é possível finalizar conferência no status atual: {$this->status->getLabel()}");
        }

        $this->status = RecebimentoStatus::CONFERIDO;
        $this->data_fim_conferencia = now();
    }

    /**
     * Aprova o recebimento
     * Nota: O repositório deve chamar save() após esta operação
     */
    public function aprovar(int $userId): void
    {
        if (!$this->status->canTransitionTo(RecebimentoStatus::APROVADO)) {
            throw new \DomainException("Não é possível aprovar no status atual: {$this->status->getLabel()}");
        }

        $this->status = RecebimentoStatus::APROVADO;
        $this->aprovado_por = $userId;
    }

    /**
     * Rejeita o recebimento
     * Nota: O repositório deve chamar save() após esta operação
     */
    public function rejeitar(string $motivo): void
    {
        if (!$this->status->canTransitionTo(RecebimentoStatus::REJEITADO)) {
            throw new \DomainException("Não é possível rejeitar no status atual: {$this->status->getLabel()}");
        }

        $this->status = RecebimentoStatus::REJEITADO;
        $this->observacoes = ($this->observacoes ?? '') . "\n\nREJEITADO: " . $motivo;
    }

    /**
     * Finaliza o processo de recebimento
     * Nota: O repositório deve chamar save() após esta operação
     */
    public function finalizar(): void
    {
        if (!$this->status->canTransitionTo(RecebimentoStatus::FINALIZADO)) {
            throw new \DomainException("Não é possível finalizar no status atual: {$this->status->getLabel()}");
        }

        $this->status = RecebimentoStatus::FINALIZADO;
    }

    /**
     * Calcula tempo de conferência em minutos
     */
    public function tempoConferenciaMinutos(): ?int
    {
        if (!$this->data_inicio_conferencia || !$this->data_fim_conferencia) {
            return null;
        }

        return $this->data_inicio_conferencia->diffInMinutes($this->data_fim_conferencia);
    }

    // Relationships

    public function itens(): HasMany
    {
        return $this->hasMany(RecebimentoItem::class, 'recebimento_id');
    }

    public function conferidoPor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'conferido_por');
    }

    public function aprovadoPor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'aprovado_por');
    }
}
