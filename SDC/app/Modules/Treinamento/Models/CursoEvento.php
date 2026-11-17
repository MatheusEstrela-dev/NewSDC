<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Models;

use App\Modules\Treinamento\Enums\TipoEventoCurso;
use App\Support\Database\UsaConexaoDeTreinamento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Linha do historico do curso. Append-only: nunca e editada nem apagada, por
 * isso nao tem updated_at nem soft delete.
 *
 * O autor e users.id do banco sdc - sem relacao Eloquent, porque users vive em
 * outra conexao. O nome do autor e resolvido em consulta separada pela camada
 * de leitura (GestaoCursoConsulta).
 */
class CursoEvento extends Model
{
    use UsaConexaoDeTreinamento;

    public const UPDATED_AT = null;

    protected $table = 'treinamento.curso_eventos';

    protected $fillable = [
        'treinamento_id',
        'turma_id',
        'tipo',
        'descricao',
        'dados',
        'autor_id',
    ];

    protected $casts = [
        'treinamento_id' => 'integer',
        'turma_id' => 'integer',
        'dados' => 'array',
        'autor_id' => 'integer',
        'created_at' => 'datetime',
    ];

    public function treinamento(): BelongsTo
    {
        return $this->belongsTo(Treinamento::class);
    }

    public function turma(): BelongsTo
    {
        return $this->belongsTo(Turma::class);
    }

    /**
     * O tipo nao e castado para enum de proposito: a coluna nao tem CHECK e uma
     * linha antiga pode trazer um tipo que o enum nao conhece mais. Neste caso
     * volta null e a descricao gravada segue valendo.
     */
    public function tipoEnum(): ?TipoEventoCurso
    {
        return TipoEventoCurso::tryFrom((string) $this->tipo);
    }

    public function scopePorTreinamento($query, int $treinamentoId)
    {
        return $query->where('treinamento_id', $treinamentoId);
    }
}
