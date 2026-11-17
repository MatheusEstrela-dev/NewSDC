<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Models;

use App\Modules\Treinamento\Enums\TipoEventoCronograma;
use App\Support\Database\UsaConexaoDeTreinamento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Item datado do curso (aula, prova, entrega, encontro). E o que alimenta o
 * calendario do gestor. turma_id NULL = item do curso inteiro.
 */
class CursoCronograma extends Model
{
    use SoftDeletes;
    use UsaConexaoDeTreinamento;

    protected $table = 'treinamento.curso_cronogramas';

    protected $fillable = [
        'treinamento_id',
        'turma_id',
        'titulo',
        'descricao',
        'tipo',
        'inicio',
        'fim',
        'local',
    ];

    protected $casts = [
        'treinamento_id' => 'integer',
        'turma_id' => 'integer',
        'tipo' => TipoEventoCronograma::class,
        'inicio' => 'datetime',
        'fim' => 'datetime',
    ];

    public function treinamento(): BelongsTo
    {
        return $this->belongsTo(Treinamento::class);
    }

    public function turma(): BelongsTo
    {
        return $this->belongsTo(Turma::class);
    }

    public function scopePorTreinamento($query, int $treinamentoId)
    {
        return $query->where('treinamento_id', $treinamentoId);
    }

    /** Itens que comecam dentro da janela do calendario. */
    public function scopeNoIntervalo($query, $de, $ate)
    {
        return $query->whereBetween('inicio', [$de, $ate]);
    }
}
