<?php

declare(strict_types=1);

namespace App\Models\Rat;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

/**
 * Histórico de eventos de uma ocorrência RAT.
 *
 * Registra cada ação significativa: criação, atualização de relatos,
 * finalização, remoção de envolvidos etc.
 * Imutável por design — nunca atualiza registros, apenas insere.
 *
 * @property int         $id
 * @property int         $ocorrencia_id
 * @property int|null    $user_id
 * @property string      $evento         Ex: 'envolvido.adicionado', 'ocorrencia.finalizada'
 * @property array|null  $payload        Dados adicionais do evento (JSON)
 * @property string|null $ip_address     IP do usuário
 * @property string|null $user_agent     Agente do navegador
 * @property \Carbon\Carbon $created_at  Data/hora do evento
 */
class RatOcorrenciaHistorico extends Model
{
    // Histórico é imutável — não existe updated_at
    public const UPDATED_AT = null;

    protected $table = 'rat_ocorrencia_historicos';

    protected $fillable = [
        'ocorrencia_id',
        'user_id',
        'evento',
        'payload',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'payload'    => 'array',
        'created_at' => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relacionamentos
    // -------------------------------------------------------------------------

    /** Ocorrência qual pertence este registro de histórico. */
    public function ocorrencia(): BelongsTo
    {
        return $this->belongsTo(RatOcorrencia::class, 'ocorrencia_id');
    }

    /** Usuário que realizou a ação registrada. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
