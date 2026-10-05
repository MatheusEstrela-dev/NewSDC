<?php

declare(strict_types=1);

namespace App\Modules\Pae\Models;

use App\Models\Municipio;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PaeMunicipioImpactado extends Model
{
    protected $table = 'pae_protocolo_municipios';

    protected $fillable = [
        'protocolo_id', 'municipio_id', 'na_zas', 'na_zss',
        'confirmado_por', 'confirmado_em',
    ];

    protected $casts = [
        'na_zas' => 'boolean',
        'na_zss' => 'boolean',
        'confirmado_em' => 'datetime',
    ];

    public function protocolo(): BelongsTo
    {
        return $this->belongsTo(PaeProtocolo::class, 'protocolo_id');
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    public function confirmador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmado_por');
    }
}
