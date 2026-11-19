<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Models;

use App\Models\User;
use App\Support\Database\UsaConexaoDeTreinamento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Certificado de quem ministrou o curso. No EAD aponta para o professor
 * designado (user_id); no presencial o instrutor e so um nome (user_id null).
 */
class CertificadoInstrutor extends Model
{
    use UsaConexaoDeTreinamento;

    protected $table = 'treinamento.certificados_instrutor';

    protected $fillable = [
        'treinamento_id',
        'user_id',
        'nome',
        'codigo_verificacao',
        'emitido_em',
        'emitido_por',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'emitido_em' => 'datetime',
        'emitido_por' => 'integer',
    ];

    public function treinamento(): BelongsTo
    {
        return $this->belongsTo(Treinamento::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pertenceA(?User $usuario): bool
    {
        return $usuario !== null && $this->user_id !== null && $this->user_id === (int) $usuario->getKey();
    }
}
