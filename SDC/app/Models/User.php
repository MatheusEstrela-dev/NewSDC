<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\NewAccessToken;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * Guard usado pelo Spatie Permission para resolver roles/permissões.
     * No NewSDC a UI usa sessão (guard "web"), então manter como "web" evita
     * mismatch (ex.: Gate::before -> hasRole('super-admin') retornando falso).
     *
     * @var string
     */
    protected $guard_name = 'web';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'cpf',
        'active',
        'password',
        'orgao_principal_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'active' => 'boolean',
    ];

    /**
     * Escopo para usuários com dados desatualizados há mais de 6 meses.
     */
    public function scopeOutdated(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->where('updated_at', '<', now()->subMonths(6));
    }

    /**
     * Create a new Bearer token with abilities based on user permissions
     */
    public function createTokenWithAbilities(string $name = 'api-token'): NewAccessToken
    {
        $abilities = $this->getAllPermissions()
            ->pluck('name')
            ->values()
            ->toArray();

        return $this->createToken($name, $abilities);
    }

    /**
     * Create a Bearer token with custom abilities
     */
    public function createTokenWithCustomAbilities(string $name, array $abilities): NewAccessToken
    {
        return $this->createToken($name, $abilities);
    }

    /**
     * Órgão principal do usuário (cache de performance)
     */
    public function orgaoPrincipal(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Modules\Compdec\Domain\Entities\Orgao::class, 'orgao_principal_id');
    }

    /**
     * Todos os órgãos vinculados ao usuário
     */
    public function orgaos(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(\App\Modules\Compdec\Domain\Entities\Orgao::class, 'orgao_user')
            ->withPivot('funcao', 'is_principal')
            ->withTimestamps();
    }

    /**
     * Verifica se o usuário pertence a um órgão específico
     */
    public function pertenceAoOrgao(int $orgaoId): bool
    {
        return $this->orgaos()->where('orgao_id', $orgaoId)->exists();
    }

    /**
     * Retorna os órgãos do tipo especificado
     */
    public function orgaosPorTipo(string $tipo): \Illuminate\Database\Eloquent\Collection
    {
        return $this->orgaos()->where('tipo', $tipo)->get();
    }
}
