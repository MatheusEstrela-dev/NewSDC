<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'email_verified_at' => 'datetime',
        'aceite_lgpd_em' => 'datetime',
        'last_login_at' => 'datetime',
    ];

    public function inscricoes(): MorphMany
    {
        return $this->morphMany(Inscricao::class, 'inscrito');
    }

    public function verificacoesEmail(): HasMany
    {
        return $this->hasMany(CidadaoEmailVerificacao::class);
    }

    /**
     * Cadastro que ainda nao confirmou o codigo enviado por e-mail. Nao
     * autentica (CidadaoAuthService) e pode ser sobrescrito por um novo cadastro
     * no mesmo CPF/e-mail: quem nunca provou posse do e-mail nao tem direito
     * adquirido sobre o CPF. Sem isso, bastaria cadastrar o CPF de alguem com um
     * e-mail qualquer para tranca-lo para sempre fora do portal.
     */
    public function emailVerificado(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function marcarEmailVerificado(): void
    {
        $this->forceFill(['email_verified_at' => now()])->save();
    }
}
