<?php

declare(strict_types=1);

namespace App\Modules\Pae\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class PaeTramitacao extends Model
{
    public $timestamps = false;

    protected $table = 'pae_tramit_prot';

    protected $fillable = [
        'user_id',
        'status',
        'obs',
        'protocolo_id',
    ];

    protected $casts = [
        'dt_status' => 'datetime',
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
