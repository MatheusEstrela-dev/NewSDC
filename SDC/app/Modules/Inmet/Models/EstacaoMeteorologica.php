<?php

declare(strict_types=1);

namespace App\Modules\Inmet\Models;

use App\Modules\Inmet\Enums\TipoEstacao;
use App\Modules\Inmet\Enums\StatusEstacao;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EstacaoMeteorologica extends Model
{
    use SoftDeletes;

    protected $table = 'estacoes_meteorologicas';

    protected $fillable = [
        'codigo',
        'nome',
        'municipio',
        'uf',
        'latitude',
        'longitude',
        'altitude',
        'tipo',
        'status',
        'situacao',
    ];
    // geom fica de fora de proposito: e escrita por SQL cru no
    // InmetRepository, porque exige ST_SetSRID(ST_MakePoint(...)), que o
    // Eloquent nao expressa.

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'altitude' => 'decimal:2',
        'tipo' => TipoEstacao::class,
        'status' => StatusEstacao::class,
    ];

    // ========================================
    // SCOPES
    // ========================================

    public function scopeAtivas($query)
    {
        return $query->where('status', StatusEstacao::ATIVO);
    }

    public function scopePorUf($query, string $uf)
    {
        return $query->where('uf', strtoupper($uf));
    }

    public function scopePorTipo($query, TipoEstacao $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    // ========================================
    // METODOS DE NEGOCIO
    // ========================================

    public function ativar(): self
    {
        $this->status = StatusEstacao::ATIVO;
        return $this;
    }

    public function desativar(): self
    {
        $this->status = StatusEstacao::INATIVO;
        return $this;
    }

    public function colocarEmManutencao(): self
    {
        $this->status = StatusEstacao::MANUTENCAO;
        return $this;
    }

    public function isAtiva(): bool
    {
        return $this->status === StatusEstacao::ATIVO;
    }

    public function isAutomatica(): bool
    {
        return $this->tipo === TipoEstacao::AUTOMATICA;
    }

    public function getCoordenadas(): array
    {
        return [
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
        ];
    }
}
