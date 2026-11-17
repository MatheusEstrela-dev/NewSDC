<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Models;

use App\Modules\Treinamento\Enums\StatusInscricao;
use App\Support\Database\UsaConexaoDeTreinamento;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Recorte datado de um curso. Diferente do curso, turma SEMPRE tem periodo e
 * vagas: e o que permite recusar inscricao fora do periodo e em turma cheia.
 *
 * Curso sem turma (EAD e o legado) continua valendo: a inscricao fica com
 * turma_id NULL e o periodo/vagas saem do proprio curso.
 */
class Turma extends Model
{
    use SoftDeletes;
    use UsaConexaoDeTreinamento;

    protected $table = 'treinamento.turmas';

    protected $fillable = [
        'treinamento_id',
        'nome',
        'data_inicio',
        'data_fim',
        'numero_vagas',
        'local',
        'municipio_id',
    ];

    protected $casts = [
        'treinamento_id' => 'integer',
        'data_inicio' => 'date',
        'data_fim' => 'date',
        'numero_vagas' => 'integer',
        'municipio_id' => 'integer',
    ];

    public function treinamento(): BelongsTo
    {
        return $this->belongsTo(Treinamento::class);
    }

    public function inscricoes(): HasMany
    {
        return $this->hasMany(Inscricao::class);
    }

    public function inscricoesAprovadas(): HasMany
    {
        return $this->hasMany(Inscricao::class)->where('status', StatusInscricao::APROVADA->value);
    }

    public function cronogramas(): HasMany
    {
        return $this->hasMany(CursoCronograma::class)->orderBy('inicio');
    }

    /** Vagas ocupadas = inscricoes APROVADAS, a mesma conta do curso. */
    public function vagasOcupadas(): int
    {
        return $this->inscricoesAprovadas()->count();
    }

    public function vagasRestantes(): int
    {
        return max(0, $this->numero_vagas - $this->vagasOcupadas());
    }

    /**
     * A inscricao so e recusada DEPOIS do fim do periodo. Antes do inicio ela e
     * inscricao antecipada, que o modulo sempre permitiu (publicar o curso
     * PLANEJADO existe justamente para abrir inscricao antes de comecar).
     */
    public function encerrouInscricoes(?CarbonInterface $agora = null): bool
    {
        // copy(): o cast devolve a instancia guardada no model, e startOfDay()
        // no Carbon mutavel mudaria o atributo para quem ler depois.
        $hoje = CarbonImmutable::parse($agora ?? now())->startOfDay();

        return $hoje->greaterThan(CarbonImmutable::parse($this->data_fim)->startOfDay());
    }

    public function scopePorTreinamento($query, int $treinamentoId)
    {
        return $query->where('treinamento_id', $treinamentoId);
    }
}
