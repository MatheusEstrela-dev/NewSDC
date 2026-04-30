<?php

declare(strict_types=1);

namespace App\Modules\Pae\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaeEmpdor extends Model
{
    use SoftDeletes;

    protected $table = 'pae_empdors';

    protected $fillable = [
        'nome',
        'cnpj',
        'status',
    ];

    public function empntos(): HasMany
    {
        return $this->hasMany(PaeEmpnto::class, 'pae_empdor_id');
    }
}
