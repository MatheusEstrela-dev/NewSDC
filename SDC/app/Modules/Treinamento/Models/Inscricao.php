<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Models;

use App\Models\User;
use App\Modules\Notificacoes\Enums\AcaoTrilha;
use App\Modules\Notificacoes\Support\TrilhaNoProtocoloPai;
use App\Modules\Treinamento\Enums\StatusFrequencia;
use App\Modules\Treinamento\Enums\Sexo;
use App\Modules\Treinamento\Enums\StatusInscricao;
use App\Support\Database\UsaConexaoDeTreinamento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inscricao extends Model
{
    use SoftDeletes;
    use TrilhaNoProtocoloPai;
    use UsaConexaoDeTreinamento;

    protected $table = 'treinamento.inscricoes';

    protected $fillable = [
        'treinamento_id',
        'inscrito_type',
        'inscrito_id',
        'status',
        'qr_code_token',
        'data_inscricao',
        'data_aprovacao',
        'aprovado_por_id',
        'observacoes',
        'sexo',
        'data_nascimento',
        'municipio_inscrito_id',
        'atualizacao_de_inscricao_id',
    ];

    protected $casts = [
        'status' => StatusInscricao::class,
        'sexo' => Sexo::class,
        'data_nascimento' => 'date',
        'data_inscricao' => 'datetime',
        'data_aprovacao' => 'datetime',
        // Conclusao congelada do EAD (spec 8.7): gravada uma vez, fora do
        // $fillable de proposito (so o servico de conclusao escreve, com forceFill).
        'concluido_em' => 'datetime',
        'aproveitamento' => 'float',
        'atualizacao_de_inscricao_id' => 'integer',
    ];

    // Relationships

    public function treinamento(): BelongsTo
    {
        return $this->belongsTo(Treinamento::class);
    }

    /**
     * O inscrito pode ser um servidor interno (App\Models\User, guard "web")
     * ou um cidadao externo (Cidadao, guard "cidadao").
     */
    public function inscrito(): MorphTo
    {
        return $this->morphTo();
    }

    public function aprovador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprovado_por_id');
    }

    public function certificado(): HasOne
    {
        return $this->hasOne(Certificado::class);
    }

    /** Avaliacao do curso: a etapa final, sem ela a inscricao nao conclui (no maximo uma). */
    public function avaliacao(): HasOne
    {
        return $this->hasOne(AvaliacaoCurso::class);
    }

    public function provaTentativas(): HasMany
    {
        return $this->hasMany(ProvaTentativa::class);
    }

    /** Inscricao de atualizacao: pessoa formada em versao anterior que aproveitou aquela conclusao. */
    public function eAtualizacao(): bool
    {
        return $this->atualizacao_de_inscricao_id !== null;
    }

    public function estaConcluida(): bool
    {
        return $this->concluido_em !== null;
    }

    // Business Logic

    /** Presencas contadas em lote por precarregarFrequencia (evita 1 count por inscricao). */
    private ?int $presencasPrecarregadas = null;

    /**
     * Prepara as inscricoes de UM treinamento para o calculo de frequencia sem
     * N+1: treinamento (com modulos) e presencas de todas entram em 2 consultas,
     * em vez de 3 por inscricao.
     *
     * @param  iterable<int, Inscricao>  $inscricoes
     */
    public static function precarregarFrequencia(iterable $inscricoes, Treinamento $treinamento): void
    {
        $treinamento->loadMissing('modulos');
        $lista = collect($inscricoes);

        $presencas = $lista->isEmpty() ? collect() : Frequencia::query()
            ->whereIn('inscricao_id', $lista->pluck('id'))
            ->whereIn('status', self::statusQueContamPresenca())
            ->selectRaw('inscricao_id, count(*) as total')
            ->groupBy('inscricao_id')
            ->pluck('total', 'inscricao_id');

        foreach ($lista as $inscricao) {
            $inscricao->setRelation('treinamento', $treinamento);
            $inscricao->presencasPrecarregadas = (int) ($presencas[$inscricao->id] ?? 0);
        }
    }

    /** @return list<string> */
    private static function statusQueContamPresenca(): array
    {
        return [StatusFrequencia::PRESENTE->value, StatusFrequencia::JUSTIFICADA->value];
    }

    public function calcularPercentualFrequencia(): float
    {
        $totalModulos = $this->totalDeModulos();

        if ($totalModulos === 0) {
            return 0;
        }

        $presencas = $this->presencasPrecarregadas ?? Frequencia::where('inscricao_id', $this->id)
            ->whereIn('status', self::statusQueContamPresenca())
            ->count();

        return ($presencas / $totalModulos) * 100;
    }

    public function estaAprovadoPorFrequencia(): bool
    {
        return $this->percentualAprovaPorFrequencia($this->calcularPercentualFrequencia());
    }

    /** Compara um percentual ja calculado com o minimo do curso (evita recalcular). */
    public function percentualAprovaPorFrequencia(float $percentual): bool
    {
        return $percentual >= $this->treinamento->percentual_frequencia_minimo;
    }

    private function totalDeModulos(): int
    {
        $treinamento = $this->treinamento;

        return $treinamento->relationLoaded('modulos')
            ? $treinamento->modulos->count()
            : $treinamento->modulos()->count();
    }

    // Scopes

    public function scopePendentes($query)
    {
        return $query->where('status', StatusInscricao::PENDENTE->value);
    }

    public function scopeAprovadas($query)
    {
        return $query->where('status', StatusInscricao::APROVADA->value);
    }

    public function scopePorTreinamento($query, int $treinamentoId)
    {
        return $query->where('treinamento_id', $treinamentoId);
    }

    // ─── Trilha de acoes no protocolo pai ───────────────────────────────────

    public function protocoloDaTrilhaClasse(): string
    {
        return Treinamento::class;
    }

    public function protocoloDaTrilhaChave(): int|string|null
    {
        return $this->treinamento_id;
    }

    public function acaoNaTrilhaDoProtocolo(): AcaoTrilha
    {
        return AcaoTrilha::Relacionado;
    }

    public function rotuloNaTrilhaDoProtocolo(): ?string
    {
        return 'uma inscricao';
    }
}
