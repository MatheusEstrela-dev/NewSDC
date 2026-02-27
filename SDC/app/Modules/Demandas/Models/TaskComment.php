<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Domain\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskComment extends Model
{
    protected $table = 'task_comments';

    protected $fillable = [
        'task_id',
        'user_id',
        'tipo',
        'conteudo',
        'interno',
        'enviado_email',
        'metadata',
    ];

    protected $casts = [
        'interno' => 'boolean',
        'enviado_email' => 'boolean',
        'metadata' => 'array',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
