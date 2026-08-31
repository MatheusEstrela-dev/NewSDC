<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Models;

use App\Modules\Pmda\Enums\PmdaEventoTipo;
use App\Modules\Pmda\Enums\PmdaStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Uma linha por transicao do plano. Append-only: nada aqui e atualizado depois
 * de gravado — e o que permite o PMDA passar por mais de um ciclo de analise sem
 * perder o anterior.
 */
class PmdaPlanoEvento extends Model
{
    protected $table = 'pmda_plano_eventos';

    protected $fillable = [
        'pmda_plano_id', 'tipo', 'de_status', 'para_status',
        'motivo', 'usuario_id', 'responsavel', 'ocorrido_em',
    ];

    protected $casts = [
        'tipo'        => PmdaEventoTipo::class,
        'de_status'   => PmdaStatus::class,
        'para_status' => PmdaStatus::class,
        'ocorrido_em' => 'datetime',
    ];

    public function plano(): BelongsTo
    {
        return $this->belongsTo(PmdaPlano::class, 'pmda_plano_id');
    }

    /**
     * Registra o evento resolvendo o nome do responsavel na hora.
     *
     * O nome fica congelado na linha de proposito: a serie historica precisa dizer
     * quem decidiu NAQUELE momento, e nao quem o id aponta hoje.
     */
    public static function registrar(
        PmdaPlano $plano,
        PmdaEventoTipo $tipo,
        ?PmdaStatus $de,
        ?PmdaStatus $para,
        ?int $usuarioId = null,
        ?string $motivo = null,
        ?string $responsavel = null,
    ): self {
        return self::create([
            'pmda_plano_id' => $plano->id,
            'tipo'          => $tipo,
            'de_status'     => $de,
            'para_status'   => $para,
            'motivo'        => $motivo,
            'usuario_id'    => $usuarioId,
            'responsavel'   => $responsavel ?: ($usuarioId ? \App\Models\User::find($usuarioId)?->name : null),
            'ocorrido_em'   => now(),
        ]);
    }
}
