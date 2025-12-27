<?php

namespace App\Modules\Tdap\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class ProductLote extends Model
{
    protected $table = 'tdap_product_lotes';

    protected $fillable = [
        'product_id',
        'numero_lote',
        'data_entrada',
        'data_fabricacao',
        'data_validade',
        'quantidade_inicial',
        'quantidade_atual',
        'localizacao',
        'observacoes',
    ];

    protected $casts = [
        'data_entrada' => 'datetime',
        'data_fabricacao' => 'date',
        'data_validade' => 'date',
        'quantidade_inicial' => 'integer',
        'quantidade_atual' => 'integer',
    ];

    // Business Logic Methods

    /**
     * Verifica se o lote está vencido
     */
    public function isVencido(): bool
    {
        if ($this->data_validade === null) {
            return false;
        }

        return $this->data_validade->isPast();
    }

    /**
     * Verifica se o lote está próximo do vencimento
     */
    public function isProximoVencimento(int $diasAlerta = 30): bool
    {
        if ($this->data_validade === null) {
            return false;
        }

        $dataLimite = Carbon::now()->addDays($diasAlerta);

        return $this->data_validade->lessThanOrEqualTo($dataLimite) && !$this->isVencido();
    }

    /**
     * Retorna dias restantes até o vencimento
     */
    public function diasAteVencimento(): ?int
    {
        if ($this->data_validade === null) {
            return null;
        }

        return Carbon::now()->diffInDays($this->data_validade, false);
    }

    /**
     * Verifica se tem quantidade disponível
     */
    public function temEstoqueDisponivel(): bool
    {
        return $this->quantidade_atual > 0;
    }

    /**
     * Verifica se pode atender uma quantidade solicitada
     */
    public function podeAtender(int $quantidadeSolicitada): bool
    {
        return $this->quantidade_atual >= $quantidadeSolicitada;
    }

    /**
     * Baixa quantidade do lote
     * Nota: O repositório deve chamar save() após esta operação
     */
    public function baixarQuantidade(int $quantidade): void
    {
        if ($quantidade > $this->quantidade_atual) {
            throw new \DomainException(
                "Quantidade solicitada ({$quantidade}) maior que disponível ({$this->quantidade_atual})"
            );
        }

        $this->quantidade_atual -= $quantidade;
    }

    /**
     * Adiciona quantidade ao lote
     * Nota: O repositório deve chamar save() após esta operação
     */
    public function adicionarQuantidade(int $quantidade): void
    {
        $this->quantidade_atual += $quantidade;
    }

    /**
     * Calcula percentual de consumo do lote
     */
    public function percentualConsumido(): float
    {
        if ($this->quantidade_inicial === 0) {
            return 0;
        }

        $consumido = $this->quantidade_inicial - $this->quantidade_atual;

        return ($consumido / $this->quantidade_inicial) * 100;
    }

    // Relationships

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function movimentacoes(): HasMany
    {
        return $this->hasMany(Movimentacao::class, 'lote_id');
    }
}
