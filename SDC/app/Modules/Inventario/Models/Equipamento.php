<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Models;

use App\Models\User;
use App\Modules\Inventario\Enums\SituacaoEquipamento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Equipamento extends Model
{
    use SoftDeletes;

    protected $table = 'inventario_ti_equipamentos';

    protected $fillable = [
        'nome', 'patrimonio', 'numero_serie', 'ramal', 'categoria_id', 'estacao_id',
        'user_id', 'unidade', 'diretoria', 'situacao', 'emprestavel', 'quantidade',
        'foto_path', 'observacao',
    ];

    protected $casts = [
        'emprestavel' => 'boolean',
        'quantidade' => 'integer',
        'situacao' => SituacaoEquipamento::class,
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaInventario::class, 'categoria_id');
    }

    public function estacao(): BelongsTo
    {
        return $this->belongsTo(Estacao::class, 'estacao_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function movimentacoes(): HasMany
    {
        return $this->hasMany(Movimentacao::class, 'equipamento_id');
    }
}
