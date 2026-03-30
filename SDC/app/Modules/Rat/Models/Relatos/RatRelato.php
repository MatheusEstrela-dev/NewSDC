<?php

declare(strict_types=1);

namespace App\Modules\Rat\Models\Relatos;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RatRelato - Model Base para relatos polimórficos RAT
 */
abstract class RatRelato extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $fillable = [
        'ocorrencia_id',
        'usuario_id',
        'type',
        'data_criacao',
        'data_atualizacao',
    ];

    protected $casts = [
        'data_criacao' => 'datetime',
        'data_atualizacao' => 'datetime',
    ];

    /**
     * Relação: Ocorrência
     */
    public function ocorrencia(): BelongsTo
    {
        return $this->belongsTo(
            'App\Modules\Rat\Models\RatOcorrenciaRelato',
            'ocorrencia_id'
        );
    }

    /**
     * Relação: Usuário
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
