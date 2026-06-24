<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PmdaRepresentante extends Model
{
    use SoftDeletes;

    protected $table = 'pmda_representantes';

    protected $fillable = [
        'pmda_comunidade_id', 'nome', 'tel', 'email', 'cpf', 'whatsapp',
    ];

    public function comunidade(): BelongsTo
    {
        return $this->belongsTo(PmdaComunidade::class, 'pmda_comunidade_id');
    }
}
