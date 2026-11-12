<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Models;

use App\Support\Database\UsaConexaoDeTreinamento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Questao de multipla escolha. Exclusao logica: tentativas ja enviadas
 * continuam apontando para ela (spec 8.7).
 */
class ProvaQuestao extends Model
{
    use SoftDeletes, UsaConexaoDeTreinamento;

    protected $table = 'treinamento.prova_questoes';

    protected $fillable = ['prova_id', 'enunciado', 'ordem'];

    protected $casts = [
        'prova_id' => 'integer',
        'ordem' => 'integer',
    ];

    public function prova(): BelongsTo
    {
        return $this->belongsTo(Prova::class);
    }

    /** Alternativas ativas, na ordem definida pelo professor. */
    public function alternativas(): HasMany
    {
        return $this->hasMany(ProvaAlternativa::class)->orderBy('ordem')->orderBy('id');
    }
}
