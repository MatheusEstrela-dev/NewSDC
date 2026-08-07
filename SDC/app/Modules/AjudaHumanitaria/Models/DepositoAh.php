<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Models;

use App\Models\Municipio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Deposito de material de Ajuda Humanitaria.
 *
 * Migrado de aju_deposito. O nome leva o sufixo Ah porque Deposito e termo
 * generico demais para o namespace de Models do projeto.
 *
 * A coluna ponto e geography(Point,4326) e nao entra em $fillable de proposito:
 * escrita de geometria passa por SQL espacial, nao por mass assignment.
 */
class DepositoAh extends Model
{
    protected $table = 'ajuda_h_depositos';

    protected $fillable = [
        'nome',
        'abreviacao',
        'municipio_id',
        'endereco',
        'ativo',
        'codigo_legado',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    public function saldos(): HasMany
    {
        return $this->hasMany(SaldoEstoqueAh::class, 'deposito_id');
    }

    public function liberacoes(): HasMany
    {
        return $this->hasMany(LiberacaoAh::class, 'deposito_id');
    }
}
