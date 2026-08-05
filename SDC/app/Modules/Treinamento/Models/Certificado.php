<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Models;

use App\Models\User;
use App\Modules\Treinamento\Enums\StatusCertificado;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Certificado extends Model
{
    use SoftDeletes;

    protected $table = 'certificados';

    protected $fillable = [
        'inscricao_id',
        'treinamento_id',
        'status',
        'emitido_em',
        'criado_por',
    ];

    protected $casts = [
        'status' => StatusCertificado::class,
        'emitido_em' => 'datetime',
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

    public function marcarComoGerado(): void
    {
        $this->update([
            'status' => StatusCertificado::GERADO,
            'emitido_em' => now(),
        ]);
    }
}
