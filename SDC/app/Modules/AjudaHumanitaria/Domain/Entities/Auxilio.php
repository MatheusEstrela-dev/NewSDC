<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\Entities;

use App\Modules\AjudaHumanitaria\Domain\ValueObjects\TipoAuxilio;
use App\Modules\AjudaHumanitaria\Domain\ValueObjects\StatusAuxilio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Entity: Auxílio Distribuído
 *
 * Aggregate Root para auxílios entregues a beneficiários
 */
class Auxilio extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'auxilios';

    protected $fillable = [
        'tipo_auxilio',
        'descricao',
        'data_distribuicao',
        'beneficiario_id',
        'abrigo_id',
        'valor_monetario',
        'responsavel_entrega',
        'comprovante_path',
        'observacoes',
        'status',
        'created_by',
    ];

    protected $casts = [
        'data_distribuicao' => 'date',
        'valor_monetario' => 'decimal:2',
        'tipo_auxilio' => TipoAuxilio::class,
        'status' => StatusAuxilio::class,
    ];

    /**
     * Relacionamento: Beneficiário que recebeu
     */
    public function beneficiario(): BelongsTo
    {
        return $this->belongsTo(Beneficiario::class, 'beneficiario_id');
    }

    /**
     * Relacionamento: Abrigo onde foi distribuído
     */
    public function abrigo(): BelongsTo
    {
        return $this->belongsTo(Abrigo::class, 'abrigo_id');
    }

    /**
     * Relacionamento: Itens do auxílio (para kits)
     */
    public function itens(): HasMany
    {
        return $this->hasMany(ItemAuxilio::class, 'auxilio_id');
    }

    /**
     * Relacionamento: Movimentações de estoque geradas
     */
    public function movimentacoesEstoque(): HasMany
    {
        return $this->hasMany(MovimentacaoEstoque::class, 'auxilio_id');
    }

    /**
     * Scope: Filtrar por status
     */
    public function scopeStatus($query, StatusAuxilio|string $status)
    {
        $statusValue = $status instanceof StatusAuxilio ? $status->value : $status;
        return $query->where('status', $statusValue);
    }

    /**
     * Scope: Filtrar por tipo
     */
    public function scopeTipo($query, TipoAuxilio|string $tipo)
    {
        $tipoValue = $tipo instanceof TipoAuxilio ? $tipo->value : $tipo;
        return $query->where('tipo_auxilio', $tipoValue);
    }

    /**
     * Scope: Auxílios entregues
     */
    public function scopeEntregues($query)
    {
        return $query->where('status', StatusAuxilio::ENTREGUE->value);
    }

    /**
     * Scope: Auxílios planejados
     */
    public function scopePlanejados($query)
    {
        return $query->where('status', StatusAuxilio::PLANEJADO->value);
    }

    /**
     * Scope: Filtrar por período
     */
    public function scopePeriodo($query, $dataInicio, $dataFim)
    {
        return $query->whereBetween('data_distribuicao', [$dataInicio, $dataFim]);
    }

    /**
     * Business Logic: Pode ser entregue?
     */
    public function podeSerEntregue(): bool
    {
        return $this->status === StatusAuxilio::PLANEJADO
            && $this->beneficiario->pode_receber_auxilio;
    }

    /**
     * Business Logic: Marcar como entregue
     */
    public function marcarComoEntregue(string $comprovanteThe): void
    {
        if (!$this->podeSerEntregue()) {
            throw new \RuntimeException('Auxílio não pode ser marcado como entregue');
        }

        $this->update([
            'status' => StatusAuxilio::ENTREGUE,
            'comprovante_path' => $comprovantePath,
        ]);
    }

    /**
     * Business Logic: Cancelar auxílio
     */
    public function cancelar(): void
    {
        if ($this->status === StatusAuxilio::ENTREGUE) {
            throw new \RuntimeException('Não é possível cancelar auxílio já entregue');
        }

        $this->update(['status' => StatusAuxilio::CANCELADO]);
    }

    /**
     * Business Logic: É auxílio monetário?
     */
    public function isMonetario(): bool
    {
        return $this->tipo_auxilio === TipoAuxilio::MONETARIO;
    }

    /**
     * Business Logic: Total de itens (para kits)
     */
    public function getTotalItens(): int
    {
        return $this->itens()->sum('quantidade');
    }
}
