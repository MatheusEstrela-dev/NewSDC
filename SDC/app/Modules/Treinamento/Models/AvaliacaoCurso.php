<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Models;

use App\Modules\Treinamento\Enums\NotaAvaliacaoCurso;
use App\Support\Database\UsaConexaoDeTreinamento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Avaliacao do curso, a etapa final obrigatoria: uma por inscricao (UNIQUE no
 * banco), gravada pelo proprio aluno e imutavel. Sem ela a inscricao nao
 * conclui (ConclusaoService::registrar). Sem SoftDeletes de proposito: a
 * conclusao ja congelada nao depende mais dela, e apaga-la so perderia o dado.
 */
class AvaliacaoCurso extends Model
{
    use UsaConexaoDeTreinamento;

    protected $table = 'treinamento.curso_avaliacoes';

    protected $fillable = [
        'inscricao_id',
        'treinamento_id',
        'nota',
        'comentario',
    ];

    protected $casts = [
        'inscricao_id' => 'integer',
        'treinamento_id' => 'integer',
        'nota' => NotaAvaliacaoCurso::class,
    ];

    // Relationships

    public function inscricao(): BelongsTo
    {
        return $this->belongsTo(Inscricao::class);
    }

    public function treinamento(): BelongsTo
    {
        return $this->belongsTo(Treinamento::class);
    }

    // Scopes

    public function scopePorTreinamento($query, int $treinamentoId)
    {
        return $query->where('treinamento_id', $treinamentoId);
    }
}
