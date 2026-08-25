<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Models;

use App\Models\Municipio;
use App\Models\User;
use App\Modules\Pmda\Enums\SolicitacaoComunidadeStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Solicitacao de inclusao de comunidade feita pelo municipio e analisada
 * pela CEDEC (tabela pmda_comunidade_solicitacoes).
 */
class ComunidadeSolicitacao extends Model
{
    protected $table = 'pmda_comunidade_solicitacoes';

    protected $fillable = [
        'municipio_id', 'pmda_plano_id', 'nome', 'latitude', 'longitude',
        'status', 'comunidade_id', 'solicitado_por', 'analisado_por',
        'analisado_em', 'motivo_rejeicao',
    ];

    protected $casts = [
        'status'       => SolicitacaoComunidadeStatus::class,
        'analisado_em' => 'datetime',
    ];

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    public function plano(): BelongsTo
    {
        return $this->belongsTo(PmdaPlano::class, 'pmda_plano_id');
    }

    public function comunidade(): BelongsTo
    {
        return $this->belongsTo(Comunidade::class, 'comunidade_id');
    }

    public function solicitadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitado_por');
    }

    public function analisadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'analisado_por');
    }
}
