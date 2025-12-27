<?php

namespace App\Modules\Tdap\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecebimentoItem extends Model
{
    protected $table = 'tdap_recebimento_itens';

    protected $fillable = [
        'recebimento_id',
        'product_id',
        'quantidade_nota',
        'quantidade_conferida',
        'numero_lote',
        'data_fabricacao',
        'data_validade',
        'tem_avaria',
        'tipo_avaria',
        'quantidade_avariada',
        'foto_avaria',
        'observacoes',
    ];

    protected $casts = [
        'quantidade_nota' => 'integer',
        'quantidade_conferida' => 'integer',
        'quantidade_avariada' => 'integer',
        'data_fabricacao' => 'date',
        'data_validade' => 'date',
        'tem_avaria' => 'boolean',
    ];

    // Business Logic Methods

    /**
     * Verifica se a quantidade confere com a nota
     */
    public function quantidadeConfere(): bool
    {
        return $this->quantidade_conferida === $this->quantidade_nota;
    }

    /**
     * Calcula divergência entre nota e conferido
     */
    public function calcularDivergencia(): int
    {
        return $this->quantidade_conferida - $this->quantidade_nota;
    }

    /**
     * Verifica se a validade é aceitável
     */
    public function validadeAceitavel(int $diasMinimos = 90): bool
    {
        if ($this->data_validade === null) {
            return true; // Produtos sem validade são aceitos
        }

        $dataLimite = now()->addDays($diasMinimos);

        return $this->data_validade->greaterThan($dataLimite);
    }

    /**
     * Registra avaria
     * Nota: O repositório deve chamar save() após esta operação
     */
    public function registrarAvaria(string $tipo, int $quantidade, ?string $fotoPath = null): void
    {
        $this->tem_avaria = true;
        $this->tipo_avaria = $tipo;
        $this->quantidade_avariada = $quantidade;
        $this->foto_avaria = $fotoPath;
    }

    /**
     * Quantidade aceita (conferida - avariada)
     */
    public function quantidadeAceita(): int
    {
        return $this->quantidade_conferida - ($this->quantidade_avariada ?? 0);
    }

    // Relationships

    public function recebimento(): BelongsTo
    {
        return $this->belongsTo(Recebimento::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
