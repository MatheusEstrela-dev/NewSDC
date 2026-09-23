<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Estacao extends Model
{
    protected $table = 'inventario_ti_estacoes';

    protected $fillable = ['nome', 'ponto_rede', 'user_id'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function equipamentos(): HasMany
    {
        return $this->hasMany(Equipamento::class, 'estacao_id');
    }
}
