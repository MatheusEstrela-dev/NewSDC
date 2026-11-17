<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Models;

use App\Support\Database\UsaConexaoDeTreinamento;
use Illuminate\Database\Eloquent\Model;

/**
 * Token do QR Code de um servidor (user_id do sdc, sem FK). Um por servidor.
 */
class CredencialQr extends Model
{
    use UsaConexaoDeTreinamento;

    protected $table = 'treinamento.credenciais_qr';

    protected $fillable = [
        'user_id',
        'token',
        'gerado_em',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'gerado_em' => 'datetime',
    ];
}
