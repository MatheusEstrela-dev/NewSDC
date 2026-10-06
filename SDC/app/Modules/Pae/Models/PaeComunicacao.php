<?php

declare(strict_types=1);

namespace App\Modules\Pae\Models;

use App\Models\Municipio;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PaeComunicacao extends Model
{
    protected $table = 'pae_comunicacoes';

    protected $fillable = [
        'protocolo_id', 'origem_tipo', 'origem_id', 'destinatario_tipo',
        'municipio_id', 'motivos', 'status', 'dt_envio', 'num_sei',
        'comprovante_path', 'comprovante_nome_original', 'comprovante_mime',
        'comprovante_tamanho_bytes', 'registrado_por', 'registrado_em',
    ];

    protected $casts = [
        'dt_envio' => 'date',
        'registrado_em' => 'datetime',
        'comprovante_tamanho_bytes' => 'integer',
    ];

    public function protocolo(): BelongsTo
    {
        return $this->belongsTo(PaeProtocolo::class, 'protocolo_id');
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    public function registrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
