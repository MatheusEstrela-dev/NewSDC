<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Models;

use App\Models\User;
use App\Modules\Notificacoes\Enums\AcaoTrilha;
use App\Modules\Notificacoes\Support\TrilhaNoProtocoloPai;
use App\Modules\Treinamento\Enums\StatusInscricao;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inscricao extends Model
{
    use SoftDeletes;
    use TrilhaNoProtocoloPai;

    protected $table = 'inscricoes';

    protected $fillable = [
        'treinamento_id',
        'user_id',
        'status',
        'data_inscricao',
        'data_aprovacao',
        'aprovado_por_id',
        'observacoes',
    ];

    protected $casts = [
        'status' => StatusInscricao::class,
        'data_inscricao' => 'datetime',
        'data_aprovacao' => 'datetime',
    ];

    // Relationships

    public function treinamento(): BelongsTo
    {
        return $this->belongsTo(Treinamento::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function aprovador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprovado_por_id');
    }

    // Business Logic

    public function calcularPercentualFrequencia(): float
    {
        $totalModulos = $this->treinamento->modulos()->count();

        if ($totalModulos === 0) {
            return 0;
        }

        $presencas = Frequencia::where('user_id', $this->user_id)
            ->whereIn('modulo_id', $this->treinamento->modulos()->pluck('id'))
            ->whereIn('status', ['PRESENTE', 'JUSTIFICADA'])
            ->count();

        return ($presencas / $totalModulos) * 100;
    }

    public function estaAprovadoPorFrequencia(): bool
    {
        $percentual = $this->calcularPercentualFrequencia();
        return $percentual >= $this->treinamento->percentual_frequencia_minimo;
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
