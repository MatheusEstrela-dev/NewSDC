<?php

declare(strict_types=1);

namespace App\Modules\Pae\Models;

use App\Models\Municipio;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaeEmpnto extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pae_empntos';

    protected $fillable = [
        'nome',
        'status',
        'municipio_id',
        'pae_empdor_id',
        'regiao_id',
        'pae_coordenador_id',
        'm_construcao',
        'material',
        'finalidade',
        'volume',
        'pop_zas',
        'orgao_fisc',
        'coordenador',
        'tel_coordenador',
        'email_coord',
        'mina',
        'coordenador_sub',
        'tel_coordenador_sub',
        'email_coord_sub',
        'user_update',
    ];

    protected $casts = [
        'volume' => 'decimal:2',
        'pop_zas' => 'integer',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\Pae\PaeEmpntoFactory::new();
    }

    public function empdor(): BelongsTo
    {
        return $this->belongsTo(PaeEmpdor::class, 'pae_empdor_id');
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    public function protocolos(): HasMany
    {
        return $this->hasMany(PaeProtocolo::class, 'pae_empnto_id');
    }

    public function latestProtocolo(): HasOne
    {
        return $this->hasOne(PaeProtocolo::class, 'pae_empnto_id')
            ->latestOfMany('created_at');
    }
}
