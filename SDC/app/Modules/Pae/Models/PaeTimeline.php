<?php

declare(strict_types=1);

namespace App\Modules\Pae\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class PaeTimeline extends Model
{
    public $timestamps = false;

    protected $table = 'pae_timeline';

    protected $fillable = [
        'protocolo_id',
        'evento',
        'descricao',
        'user_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function protocolo(): BelongsTo
    {
        return $this->belongsTo(PaeProtocolo::class, 'protocolo_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
