<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PmdaCompdecMembro extends Model
{
    use SoftDeletes;

    protected $table = 'pmda_compdec_membros';

    protected $fillable = [
        'pmda_plano_id', 'nome', 'cargo', 'telefone',
    ];

    public function plano(): BelongsTo
    {
        return $this->belongsTo(PmdaPlano::class, 'pmda_plano_id');
    }
}
