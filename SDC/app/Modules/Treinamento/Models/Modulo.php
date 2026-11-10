<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Models;

use App\Support\Database\UsaConexaoDeTreinamento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Modulo extends Model
{
    use SoftDeletes, UsaConexaoDeTreinamento;

    protected $table = 'treinamento.modulos';

    protected $fillable = [
        'treinamento_id',
        'titulo',
        'descricao',
        'ordem',
        'carga_horaria',
        'data_prevista',
    ];

    protected $casts = [
        'ordem' => 'integer',
        'carga_horaria' => 'integer',
        'data_prevista' => 'date',
    ];

    // Relationships

    public function treinamento(): BelongsTo
    {
        return $this->belongsTo(Treinamento::class);
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ModuloItem::class)->orderBy('ordem')->orderBy('id');
    }

    public function frequencias(): HasMany
    {
        return $this->hasMany(Frequencia::class);
    }

    // Scopes

    public function scopePorTreinamento($query, int $treinamentoId)
    {
        return $query->where('treinamento_id', $treinamentoId);
    }

    public function scopeOrdenados($query)
    {
        return $query->orderBy('ordem')->orderBy('id');
    }
}
