<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Models;

use App\Models\User;
use App\Modules\Demandas\Enums\Impacto;
use App\Modules\Demandas\Enums\Prioridade;
use App\Modules\Demandas\Enums\StatusDemanda;
use App\Modules\Demandas\Enums\TipoDemanda;
use App\Modules\Demandas\Enums\Urgencia;
use App\Modules\Notificacoes\Contracts\Rastreavel;
use App\Modules\Notificacoes\Support\TrilhaDeAcoes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Domain Entity: Demanda (Tarefa Mestre)
 */
class Demanda extends Model implements Rastreavel
{
    use HasFactory, SoftDeletes, TrilhaDeAcoes;

    protected $table = 'tasks';

    protected $fillable = [
        'protocolo',
        'tipo',
        'titulo',
        'descricao',
        'status',
        'impacto',
        'urgencia',
        'prioridade',
        'solicitante_id',
        'atribuido_para_id',
        'grupo_id',
        'categoria',
        'subcategoria',
        'campos_customizados',
        'prazo_primeira_resposta',
        'primeira_resposta_em',
        'prazo_resolucao',
        'resolvido_em',
        'sla_primeira_resposta_violado',
        'sla_resolucao_violado',
        'tempo_em_aberta',
        'tempo_em_progresso',
        'tempo_total_resolucao',
    ];

    protected $casts = [
        'tipo' => TipoDemanda::class,
        'status' => StatusDemanda::class,
        'impacto' => Impacto::class,
        'urgencia' => Urgencia::class,
        'prioridade' => Prioridade::class,
        'campos_customizados' => 'array',
        'prazo_primeira_resposta' => 'datetime',
        'primeira_resposta_em' => 'datetime',
        'prazo_resolucao' => 'datetime',
        'resolvido_em' => 'datetime',
        'sla_primeira_resposta_violado' => 'boolean',
        'sla_resolucao_violado' => 'boolean',
        'tempo_em_aberta' => 'integer',
        'tempo_em_progresso' => 'integer',
        'tempo_total_resolucao' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Demanda $demanda) {
            if (empty($demanda->protocolo)) {
                $demanda->protocolo = $demanda->gerarProtocolo();
            }
            $demanda->calcularPrioridade();
        });

        static::updating(function (Demanda $demanda) {
            if ($demanda->isDirty(['impacto', 'urgencia'])) {
                $demanda->calcularPrioridade();
            }
        });
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitante_id');
    }

    public function atribuidoPara(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atribuido_para_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(DemandaComentario::class, 'task_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(DemandaAnexo::class, 'task_id');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(DemandaAprovacao::class, 'task_id');
    }

    public function slaInstance(): HasMany
    {
        return $this->hasMany(SlaInstancia::class, 'task_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(DemandaAuditLog::class, 'task_id');
    }

    public function gerarProtocolo(): string
    {
        $ano = now()->year;
        $prefix = $this->tipo->getProtocoloPrefix();

        $ultimoNumero = static::query()
            ->where('tipo', $this->tipo->value)
            ->where('protocolo', 'like', "{$prefix}-{$ano}-%")
            ->max('protocolo');

        if ($ultimoNumero) {
            preg_match('/-(\d+)$/', $ultimoNumero, $matches);
            $numero = isset($matches[1]) ? (int) $matches[1] + 1 : 1;
        } else {
            $numero = 1;
        }

        return sprintf('%s-%d-%06d', $prefix, $ano, $numero);
    }

    public function calcularPrioridade(): void
    {
        if ($this->impacto && $this->urgencia) {
            $this->prioridade = Prioridade::calcularPorMatriz(
                $this->impacto,
                $this->urgencia
            );
        }
    }

    public function assignTo(User $user): self
    {
        $this->atribuido_para_id = $user->id;
        return $this;
    }

    public function changeStatus(StatusDemanda $newStatus): self
    {
        $this->status = $newStatus;
        return $this;
    }

    public function isAtrasada(): bool
    {
        return $this->sla_resolucao_violado || $this->sla_primeira_resposta_violado;
    }

    public function requiresApproval(): bool
    {
        return $this->tipo->requiresApproval();
    }

    public function moduloNotificacao(): string
    {
        return 'demandas';
    }

    public function rotuloProtocolo(): string
    {
        $protocolo = trim((string) $this->protocolo);
        return $protocolo === '' ? 'Demanda '.$this->titulo : 'Demanda '.$protocolo;
    }

    public function donosNotificacao(): array
    {
        return array_values(array_filter([
            $this->solicitante_id === null ? null : (int) $this->solicitante_id,
            $this->atribuido_para_id === null ? null : (int) $this->atribuido_para_id,
        ], fn (?int $id): bool => $id !== null));
    }

    public function urlNotificacao(): ?string
    {
        return '/demandas/'.$this->getKey();
    }

    public function campoSituacao(): ?string
    {
        return null;
    }

    public function camposIgnoradosNaTrilha(): array
    {
        return array_merge($this->camposBaseIgnoradosNaTrilha(), [
            'primeira_resposta_em',
            'resolvido_em',
            'sla_primeira_resposta_violado',
            'sla_resolucao_violado',
            'tempo_em_aberta',
            'tempo_em_progresso',
            'tempo_total_resolucao',
            'atribuido_para_id',
            'status',
        ]);
    }
}
