<?php

declare(strict_types=1);

namespace App\Modules\Pae\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Exigibilidade do simulado no protocolo (imutavel; a mais recente vale). */
final class PaeSimuladoAvaliacao extends Model
{
    public const RESULTADOS = ['exigivel', 'dispensado'];

    /** Art. 17 (PAE para Licenca de Instalacao) e Art. 21 (metodo alternativo aprovado pela CEDEC). */
    public const MOTIVOS_DISPENSA = ['licenca_instalacao', 'metodo_alternativo'];

    public $timestamps = false;

    protected $table = 'pae_simulado_avaliacoes';

    protected $fillable = [
        'protocolo_id', 'resultado', 'motivo_dispensa', 'fundamentacao', 'num_sei',
        'chave_idempotencia', 'decidido_por', 'decidido_em',
    ];

    protected $casts = [
        'decidido_em' => 'datetime',
    ];

    public function protocolo(): BelongsTo
    {
        return $this->belongsTo(PaeProtocolo::class, 'protocolo_id');
    }

    public function decisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decidido_por');
    }
}
