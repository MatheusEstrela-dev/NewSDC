<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Models;

use App\Models\User;
use App\Modules\Tdap\Enums\AcaoTramite;
use App\Modules\Tdap\Enums\FaseCronograma;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Um passo da tramitacao do cronograma (mesmo molde de PedidoAhTramite).
 * Registro so de insercao: nao tem updated_at.
 *
 * @property int             $id
 * @property int             $cronograma_id
 * @property FaseCronograma  $fase_anterior
 * @property FaseCronograma  $fase_nova
 * @property AcaoTramite     $acao
 * @property ?int            $user_id
 * @property ?string         $observacao
 * @property \Carbon\Carbon  $created_at
 */
class CronogramaTramite extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'tdap_cronograma_tramites';

    protected $fillable = [
        'cronograma_id',
        'fase_anterior',
        'fase_nova',
        'acao',
        'user_id',
        'observacao',
    ];

    protected $casts = [
        'cronograma_id' => 'integer',
        'fase_anterior' => FaseCronograma::class,
        'fase_nova'     => FaseCronograma::class,
        'acao'          => AcaoTramite::class,
        'created_at'    => 'datetime',
    ];

    public function cronograma(): BelongsTo
    {
        return $this->belongsTo(Cronograma::class, 'cronograma_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
