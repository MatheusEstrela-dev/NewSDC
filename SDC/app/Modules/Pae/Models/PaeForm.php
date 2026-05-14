<?php

declare(strict_types=1);

namespace App\Modules\Pae\Models;

use Database\Factories\PaeFormFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PaeForm extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pae_forms';

    protected $fillable = [
        'pae_protocolo_id',
        'uuid_publico',
        'status',
        'barragem_nome',
        'emp_responsavel_nome',
        'coord_pae_nome',
        'coord_pae_email',
        'coord_mun_def_civ',
        'coord_mun_compdec',
        'metodo_construtivo',
        'num_zas',
        'nivel_emergencia',
        'objetivo',
        'contexto',
        'municipio_id',
        'pae_empnto_id',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $form) {
            if (empty($form->uuid_publico)) {
                $form->uuid_publico = (string) Str::uuid();
            }
        });
    }

    public function protocolo(): BelongsTo
    {
        return $this->belongsTo(PaeProtocolo::class, 'pae_protocolo_id');
    }

    public function apontamentos(): HasMany
    {
        return $this->hasMany(PaeFormApontamento::class, 'pae_form_id');
    }

    public function conclusao(): HasMany
    {
        return $this->hasMany(PaeFormConclusaoItem::class, 'pae_form_id');
    }

    public function anexos(): HasMany
    {
        return $this->hasMany(PaeFormAnexo::class, 'pae_form_id')->orderByDesc('created_at');
    }

    protected static function newFactory(): PaeFormFactory
    {
        return PaeFormFactory::new();
    }
}
