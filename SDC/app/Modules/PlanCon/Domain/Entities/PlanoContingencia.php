<?php

namespace App\Modules\PlanCon\Domain\Entities;

use App\Modules\PlanCon\Domain\ValueObjects\SituacaoPlano;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanoContingencia extends Model
{
    protected $table = 'planos_contingencia';

    protected $fillable = [
        'municipio_id',
        'nome',
        'descricao',
        'situacao',
        'data_aprovacao',
        'data_validade',
        'arquivo_url',
        'observacoes',
    ];

    protected $casts = [
        'situacao' => SituacaoPlano::class,
        'data_aprovacao' => 'date',
        'data_validade' => 'date',
    ];

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Municipio::class);
    }

    public function isRegular(): bool
    {
        return $this->situacao === SituacaoPlano::REGULAR;
    }

    public function isVigente(): bool
    {
        if (!$this->data_validade) {
            return true;
        }

        return $this->data_validade->isFuture();
    }
}
