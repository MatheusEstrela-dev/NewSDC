<?php

namespace App\Modules\Tdap\Domain\Entities;

use App\Modules\Tdap\Domain\ValueObjects\MovimentacaoType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Movimentacao extends Model
{
    use SoftDeletes;

    protected $table = 'tdap_movimentacoes';

    protected $fillable = [
        'numero_movimentacao',
        'tipo',
        'product_id',
        'lote_id',
        'quantidade',
        'data_movimentacao',
        'origem',
        'destino',
        'solicitante_id',
        'responsavel_id',
        'documento_referencia',
        'observacoes',
    ];

    protected $casts = [
        'tipo' => MovimentacaoType::class,
        'quantidade' => 'integer',
        'data_movimentacao' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Movimentacao $movimentacao) {
            if (empty($movimentacao->numero_movimentacao)) {
                $movimentacao->numero_movimentacao = $movimentacao->gerarNumeroMovimentacao();
            }
        });
    }

    // Business Logic Methods

    /**
     * Gera número único de movimentação
     */
    public function gerarNumeroMovimentacao(): string
    {
        $prefixo = match($this->tipo) {
            MovimentacaoType::ENTRADA => 'ENT',
            MovimentacaoType::SAIDA => 'SAI',
            MovimentacaoType::TRANSFERENCIA => 'TRF',
            MovimentacaoType::AJUSTE => 'AJU',
            MovimentacaoType::DEVOLUCAO => 'DEV',
        };

        $ano = date('Y');
        $mes = date('m');
        $random = strtoupper(Str::random(4));

        return "{$prefixo}-{$ano}{$mes}-{$random}";
    }

    /**
     * Verifica se é uma movimentação de entrada
     */
    public function isEntrada(): bool
    {
        return in_array($this->tipo, [MovimentacaoType::ENTRADA, MovimentacaoType::DEVOLUCAO]);
    }

    /**
     * Verifica se é uma movimentação de saída
     */
    public function isSaida(): bool
    {
        return $this->tipo === MovimentacaoType::SAIDA;
    }

    // Relationships

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(ProductLote::class);
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'solicitante_id');
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'responsavel_id');
    }
}
