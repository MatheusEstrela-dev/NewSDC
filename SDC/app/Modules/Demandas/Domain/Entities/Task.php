<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Domain\Entities;

use App\Models\User;
use App\Modules\Demandas\Domain\ValueObjects\Impacto;
use App\Modules\Demandas\Domain\ValueObjects\Prioridade;
use App\Modules\Demandas\Domain\ValueObjects\TaskStatus;
use App\Modules\Demandas\Domain\ValueObjects\TipoTask;
use App\Modules\Demandas\Domain\ValueObjects\Urgencia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Domain Entity: Task (Tarefa Mestre)
 *
 * Tabela mestre que centraliza todas as demandas (Incidentes, Solicitações, Mudanças)
 * Implementa Table Inheritance Pattern conforme papiro task01.md
 *
 * @property int $id
 * @property string $protocolo
 * @property TipoTask $tipo
 * @property string $titulo
 * @property string|null $descricao
 * @property TaskStatus $status
 * @property Impacto $impacto
 * @property Urgencia $urgencia
 * @property Prioridade $prioridade
 * @property int|null $solicitante_id
 * @property int|null $atribuido_para_id
 * @property int|null $grupo_id
 * @property string|null $categoria
 * @property string|null $subcategoria
 * @property array|null $campos_customizados
 * @property \Illuminate\Support\Carbon|null $prazo_primeira_resposta
 * @property \Illuminate\Support\Carbon|null $primeira_resposta_em
 * @property \Illuminate\Support\Carbon|null $prazo_resolucao
 * @property \Illuminate\Support\Carbon|null $resolvido_em
 * @property bool $sla_primeira_resposta_violado
 * @property bool $sla_resolucao_violado
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Task extends Model
{
    use HasFactory, SoftDeletes;

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
        'tipo' => TipoTask::class,
        'status' => TaskStatus::class,
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

    protected $dates = [
        'prazo_primeira_resposta',
        'primeira_resposta_em',
        'prazo_resolucao',
        'resolvido_em',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Boot do modelo para eventos
     */
    protected static function booted(): void
    {
        // Antes de criar: gerar protocolo e calcular prioridade
        static::creating(function (Task $task) {
            if (empty($task->protocolo)) {
                $task->protocolo = $task->gerarProtocolo();
            }

            $task->calcularPrioridade();
        });

        // Antes de salvar: validar transição de status
        static::updating(function (Task $task) {
            if ($task->isDirty('status')) {
                $task->validarTransicaoStatus();
            }

            // Recalcular prioridade se impacto ou urgência mudaram
            if ($task->isDirty(['impacto', 'urgencia'])) {
                $task->calcularPrioridade();
            }
        });
    }

    /**
     * Relacionamentos
     */
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
        return $this->hasMany(TaskComment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(TaskApproval::class);
    }

    public function slaInstance(): HasMany
    {
        return $this->hasMany(TaskSlaInstance::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(TaskAuditLog::class);
    }

    /**
     * Métodos de Negócio
     */

    /**
     * Gera protocolo automático baseado no tipo
     */
    public function gerarProtocolo(): string
    {
        $ano = now()->year;
        $prefix = $this->tipo->getProtocoloPrefix();

        // Buscar último número do ano
        $ultimoNumero = static::query()
            ->where('tipo', $this->tipo->value)
            ->where('protocolo', 'like', "{$prefix}-{$ano}-%")
            ->max('protocolo');

        if ($ultimoNumero) {
            // Extrair número e incrementar
            preg_match('/-(\d+)$/', $ultimoNumero, $matches);
            $numero = isset($matches[1]) ? (int) $matches[1] + 1 : 1;
        } else {
            $numero = 1;
        }

        return sprintf('%s-%d-%06d', $prefix, $ano, $numero);
    }

    /**
     * Calcula prioridade automaticamente pela Matriz Impacto x Urgência
     */
    public function calcularPrioridade(): void
    {
        $this->prioridade = Prioridade::calcularPorMatriz(
            $this->impacto,
            $this->urgencia
        );
    }

    /**
     * Valida se a transição de status é permitida (State Machine)
     *
     * @throws \DomainException
     */
    protected function validarTransicaoStatus(): void
    {
        $statusOriginal = $this->getOriginal('status');

        if (! $statusOriginal) {
            return; // Criação inicial
        }

        $statusOriginalEnum = TaskStatus::from($statusOriginal);
        $novoStatus = $this->status;

        if (! $statusOriginalEnum->canTransitionTo($novoStatus)) {
            throw new \DomainException(
                sprintf(
                    'Transição de status inválida: %s -> %s',
                    $statusOriginalEnum->getLabel(),
                    $novoStatus->getLabel()
                )
            );
        }
    }

    /**
     * Altera o status (com validação automática)
     */
    public function changeStatus(TaskStatus $newStatus, ?User $user = null, ?string $comentario = null): self
    {
        $statusAnterior = $this->status;

        $this->status = $newStatus;
        $this->save();

        // Registrar no audit log
        TaskAuditLog::create([
            'task_id' => $this->id,
            'user_id' => $user?->id,
            'acao' => 'status_changed',
            'campo' => 'status',
            'valor_anterior' => $statusAnterior->value,
            'valor_novo' => $newStatus->value,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Adicionar comentário automático
        if ($comentario) {
            $this->comments()->create([
                'user_id' => $user?->id,
                'tipo' => 'atualizacao',
                'conteudo' => $comentario,
                'metadata' => [
                    'campo' => 'status',
                    'de' => $statusAnterior->value,
                    'para' => $newStatus->value,
                ],
            ]);
        }

        return $this;
    }

    /**
     * Atribui a tarefa para um usuário
     */
    public function assignTo(User $user, ?User $assignedBy = null): self
    {
        $anteriorId = $this->atribuido_para_id;

        $this->atribuido_para_id = $user->id;
        $this->save();

        // Audit log
        TaskAuditLog::create([
            'task_id' => $this->id,
            'user_id' => $assignedBy?->id,
            'acao' => 'assigned',
            'campo' => 'atribuido_para_id',
            'valor_anterior' => (string) $anteriorId,
            'valor_novo' => (string) $user->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return $this;
    }

    /**
     * Verifica se a tarefa está atrasada (SLA violado)
     */
    public function isAtrasada(): bool
    {
        return $this->sla_resolucao_violado || $this->sla_primeira_resposta_violado;
    }

    /**
     * Verifica se requer aprovação
     */
    public function requiresApproval(): bool
    {
        return $this->tipo->requiresApproval();
    }

    /**
     * Verifica se todas as aprovações foram concedidas
     */
    public function allApprovalsGranted(): bool
    {
        if (! $this->requiresApproval()) {
            return true;
        }

        return ! $this->approvals()
            ->where('obrigatorio', true)
            ->where('status', '!=', 'aprovado')
            ->exists();
    }

    /**
     * Scopes
     */

    public function scopeAberta($query)
    {
        return $query->where('status', TaskStatus::ABERTA);
    }

    public function scopeEmProgresso($query)
    {
        return $query->where('status', TaskStatus::EM_PROGRESSO);
    }

    public function scopeAtivas($query)
    {
        return $query->whereIn('status', [
            TaskStatus::ABERTA,
            TaskStatus::EM_ANALISE,
            TaskStatus::EM_PROGRESSO,
            TaskStatus::AGUARDANDO_TERCEIROS,
        ]);
    }

    public function scopeCritica($query)
    {
        return $query->where('prioridade', Prioridade::CRITICA);
    }

    public function scopeAtrasadas($query)
    {
        return $query->where(function ($q) {
            $q->where('sla_resolucao_violado', true)
                ->orWhere('sla_primeira_resposta_violado', true);
        });
    }

    public function scopePorTipo($query, TipoTask $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopeAtribuidasPara($query, User $user)
    {
        return $query->where('atribuido_para_id', $user->id);
    }

    public function scopeSolicitadasPor($query, User $user)
    {
        return $query->where('solicitante_id', $user->id);
    }
}
