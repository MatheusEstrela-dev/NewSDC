<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Domain\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskApproval extends Model
{
    protected $table = 'task_approvals';

    protected $fillable = [
        'task_id',
        'aprovador_id',
        'status',
        'comentario',
        'respondido_em',
        'ordem',
        'obrigatorio',
    ];

    protected $casts = [
        'respondido_em' => 'datetime',
        'obrigatorio' => 'boolean',
        'ordem' => 'integer',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function aprovador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprovador_id');
    }

    /**
     * Aprovar
     */
    public function aprovar(?string $comentario = null): self
    {
        $this->status = 'aprovado';
        $this->comentario = $comentario;
        $this->respondido_em = now();
        $this->save();

        return $this;
    }

    /**
     * Rejeitar
     */
    public function rejeitar(?string $comentario = null): self
    {
        $this->status = 'rejeitado';
        $this->comentario = $comentario;
        $this->respondido_em = now();
        $this->save();

        return $this;
    }
}
