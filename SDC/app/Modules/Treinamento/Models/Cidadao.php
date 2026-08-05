<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Conta de cidadao externo do Portal de Treinamentos (guard "cidadao").
 * Isolada de App\Models\User: nao tem roles/permissions do Spatie e nao
 * acessa nenhuma outra rota do SDC alem do proprio portal.
 */
class Cidadao extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $table = 'cidadaos';

    protected $fillable = [
        'name',
        'email',
        'cpf',
        'telefone',
        'password',
        'aceite_lgpd_em',
        'ativo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
        'ativo' => 'boolean',
        'aceite_lgpd_em' => 'datetime',
        'last_login_at' => 'datetime',
    ];

    public function inscricoes(): MorphMany
    {
        return $this->morphMany(Inscricao::class, 'inscrito');
    }
}
