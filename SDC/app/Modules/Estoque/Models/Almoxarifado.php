<?php

declare(strict_types=1);

namespace App\Modules\Estoque\Models;

use App\Models\Municipio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Deposito fisico do estoque: o filtro "saldo por almoxarifado" do #33. */
class Almoxarifado extends Model
{
    protected $table = 'estoque_almoxarifados';

    protected $fillable = ['nome', 'tipo', 'municipio_id', 'capacidade', 'ativo'];

    protected $casts = [
        'capacidade' => 'integer',
        'ativo'      => 'boolean',
    ];

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    public function lotes(): HasMany
    {
        return $this->hasMany(Lote::class, 'almoxarifado_id');
    }
}
