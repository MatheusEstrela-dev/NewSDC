<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Models;

use App\Support\Database\UsaConexaoDeTreinamento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Alternativa de uma questao. `correta` fica fora da serializacao por padrao:
 * o gabarito nunca vai ao aluno; so a area do professor o expoe, com
 * makeVisible('correta').
 */
class ProvaAlternativa extends Model
{
    use SoftDeletes, UsaConexaoDeTreinamento;

    protected $table = 'treinamento.prova_alternativas';

    protected $fillable = ['prova_questao_id', 'texto', 'correta', 'ordem'];

    protected $hidden = ['correta'];

    protected $casts = [
        'prova_questao_id' => 'integer',
        'correta' => 'boolean',
        'ordem' => 'integer',
    ];

    public function questao(): BelongsTo
    {
        return $this->belongsTo(ProvaQuestao::class, 'prova_questao_id')->withTrashed();
    }
}
