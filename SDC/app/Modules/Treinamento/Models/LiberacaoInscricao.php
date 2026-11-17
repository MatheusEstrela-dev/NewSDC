<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Models;

use App\Support\Database\UsaConexaoDeTreinamento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Liberacao, pelo gestor, de um CPF bloqueado pela regra das 3 inscricoes no
 * mesmo curso. A contagem recomeca a partir de created_at.
 */
class LiberacaoInscricao extends Model
{
    use UsaConexaoDeTreinamento;

    protected $table = 'treinamento.liberacoes_inscricao';

    protected $fillable = ['cpf', 'curso_id', 'liberado_por', 'motivo'];

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class, 'curso_id');
    }
}
