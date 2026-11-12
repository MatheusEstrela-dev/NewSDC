<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Models;

use App\Models\User;
use App\Modules\Treinamento\Enums\StatusCertificado;
use App\Support\Database\UsaConexaoDeTreinamento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Certificado extends Model
{
    use SoftDeletes;
    use UsaConexaoDeTreinamento;

    protected $table = 'treinamento.certificados';

    protected $fillable = [
        'inscricao_id',
        'treinamento_id',
        'status',
        'emitido_em',
        'criado_por',
        'codigo_verificacao',
        'aproveitamento',
        'reemitido_em',
        'cancelado_em',
        'cancelado_por',
        'cancelamento_motivo',
    ];

    protected $casts = [
        'status' => StatusCertificado::class,
        'emitido_em' => 'datetime',
        'aproveitamento' => 'float',
        'reemitido_em' => 'datetime',
        'cancelado_em' => 'datetime',
        'cancelado_por' => 'integer',
    ];

    public function inscricao(): BelongsTo
    {
        return $this->belongsTo(Inscricao::class);
    }

    public function treinamento(): BelongsTo
    {
        return $this->belongsTo(Treinamento::class);
    }

    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por');
    }

    public function canceladoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelado_por');
    }

    public function estaCancelado(): bool
    {
        return $this->status === StatusCertificado::CANCELADO;
    }

    public function marcarComoGerado(): void
    {
        $this->update([
            'status' => StatusCertificado::GERADO,
            'emitido_em' => now(),
        ]);
    }
}
