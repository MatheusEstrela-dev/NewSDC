<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Models;

use App\Support\Database\UsaConexaoDeTreinamento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Resposta de uma questao numa tentativa: uma por (tentativa, questao),
 * regravada a cada troca enquanto a tentativa esta aberta. `correta` e
 * gravada no envio (congela o gabarito da epoca, spec 8.7) e fica fora da
 * serializacao por padrao, como o gabarito da alternativa.
 */
class ProvaResposta extends Model
{
    use UsaConexaoDeTreinamento;

    protected $table = 'treinamento.prova_respostas';

    protected $fillable = ['prova_tentativa_id', 'prova_questao_id', 'prova_alternativa_id', 'correta'];

    protected $hidden = ['correta'];

    protected $casts = [
        'prova_tentativa_id' => 'integer',
        'prova_questao_id' => 'integer',
        'prova_alternativa_id' => 'integer',
        'correta' => 'boolean',
    ];

    public function tentativa(): BelongsTo
    {
        return $this->belongsTo(ProvaTentativa::class, 'prova_tentativa_id');
    }

    public function questao(): BelongsTo
    {
        return $this->belongsTo(ProvaQuestao::class, 'prova_questao_id')->withTrashed();
    }

    public function alternativa(): BelongsTo
    {
        return $this->belongsTo(ProvaAlternativa::class, 'prova_alternativa_id')->withTrashed();
    }
}
