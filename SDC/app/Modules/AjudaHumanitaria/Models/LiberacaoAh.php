<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Models;

use App\Models\Municipio;
use App\Models\User;
use App\Modules\AjudaHumanitaria\Enums\StatusLiberacao;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Liberacao de material para um municipio, migrada de aju_liberacao.
 *
 * Registro historico: as 3.582 linhas vieram do legado e nao ha, por ora,
 * fluxo que crie liberacao pelo sistema novo. Por isso o modelo nao declara
 * $fillable de escrita ampla.
 *
 * solicitante_id fica nulo em tudo que veio do legado. aju_liberacao.id_usuario
 * coincide numericamente com users.id, mas e coincidencia: o id 73 no NewSDC e
 * conta de municipio, nao o oficial da CEDEC que autorizou. O id original esta
 * preservado em payload_legado ate existir um De-Para real.
 */
class LiberacaoAh extends Model
{
    use SoftDeletes;

    protected $table = 'ajuda_h_liberacoes';

    protected $fillable = [
        'municipio_id',
        'deposito_id',
        'solicitante_id',
        'cargo_id',
        'beneficiario',
        'data_libera',
        'data_limite',
        'status',
        'observacao',
        'cancelado_em',
        'motivo_cancelamento',
        'evento',
        'payload_legado',
        'codigo_legado',
    ];

    protected $casts = [
        'data_libera'    => 'date',
        'data_limite'    => 'date',
        'cancelado_em'   => 'datetime',
        'status'         => StatusLiberacao::class,
        'payload_legado' => 'array',
    ];

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    public function deposito(): BelongsTo
    {
        return $this->belongsTo(DepositoAh::class, 'deposito_id');
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitante_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(LiberacaoItemAh::class, 'liberacao_id');
    }

    public function recibos(): HasMany
    {
        return $this->hasMany(LiberacaoReciboAh::class, 'liberacao_id');
    }
}
