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
        'password',
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
    ];

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
}
