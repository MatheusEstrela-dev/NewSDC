<?php

namespace App\Models;

use App\Traits\HasHierarchy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\NewAccessToken;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, HasHierarchy, SoftDeletes;

    /**
     * Guard usado pelo Spatie Permission para resolver roles/permissões.
     * No NewSDC a UI usa sessão (guard "web"), então manter como "web" evita
     * mismatch (ex.: Gate::before -> hasRole('super-admin') retornando falso).
     *
     * @var string
     */
    protected $guard_name = 'web';

    /**
     * Spatie usa roles.name como identificador, mas o NewSDC guarda o codigo
     * estavel em roles.slug e o nome de exibicao em roles.name.
     */
    public function hasRole($roles, ?string $guard = null): bool
    {
        $this->loadMissing('roles');

        if (is_string($roles) && str_contains($roles, '|')) {
            $roles = explode('|', $roles);
        }

        if ($roles instanceof \BackedEnum) {
            $roles = $roles->value;
        }

        if (is_int($roles) || (is_string($roles) && ctype_digit($roles))) {
            return $this->roles
                ->when($guard, fn ($q) => $q->where('guard_name', $guard))
                ->contains('id', (int) $roles);
        }

        if (is_string($roles)) {
            return $this->roles
                ->when($guard, fn ($q) => $q->where('guard_name', $guard))
                ->contains(fn ($role) => $role->name === $roles || $role->slug === $roles);
        }

        if ($roles instanceof Role) {
            return $this->roles->contains($roles->getKeyName(), $roles->getKey());
        }

        if (is_array($roles) || $roles instanceof \Illuminate\Support\Collection) {
            foreach ($roles as $role) {
                if ($this->hasRole($role, $guard)) {
                    return true;
                }
            }

            return false;
        }

        throw new \TypeError('Unsupported type for $roles parameter to hasRole().');
    }

    public function hasAnyRole(...$roles): bool
    {
        return $this->hasRole($roles);
    }



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
        'status',
        'password',
        'orgao_principal_id',
        'last_login_at',
        'last_login_ip',
        'user_agent',
        'last_login_browser',
        'created_by',
        'updated_by',
        'notification_update_mode',
        'pending_expires_at',
        'must_change_password',
        'password_changed_at',
        'welcome_tour_completed_at',
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
        'last_login_at' => 'datetime',
        'password' => 'hashed',
        'active' => 'boolean',
        'pending_expires_at' => 'datetime',
        'password_changed_at' => 'datetime',
        'welcome_tour_completed_at' => 'datetime',
        'must_change_password' => 'boolean',
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
     * Órgão principal do usuário
     */
    public function orgaoPrincipal(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Modules\Compdec\Models\Orgao::class, 'orgao_principal_id');
    }

    /**
     * Todos os órgãos vinculados ao usuário
     */
    public function orgaos(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(\App\Modules\Compdec\Models\Orgao::class, 'compdec_orgao_user', 'user_id', 'orgao_id')
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
     * Retorna os orgaos do tipo especificado
     */
    public function orgaosPorTipo(string $tipo): \Illuminate\Database\Eloquent\Collection
    {
        return $this->orgaos()->where('tipo', $tipo)->get();
    }

    /**
     * Usuario que criou este registro
     */
    public function createdBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Usuario que atualizou este registro por ultimo
     */
    public function updatedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Escopo para usuarios inativos (sem login ha mais de 6 meses)
     */
    public function scopeInactive(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->where(function ($q) {
            $q->whereNull('last_login_at')
                ->orWhere('last_login_at', '<', now()->subMonths(6));
        })->where('active', true);
    }

    /**
     * Escopo para usuarios por status
     */
    public function scopeByStatus(\Illuminate\Database\Eloquent\Builder $query, string $status): void
    {
        $query->where('status', $status);
    }

    /**
     * Registra o ultimo login do usuario.
     *
     * Mantem o status em 'pending' enquanto must_change_password=true para preservar
     * o fluxo de primeiro acesso (a promocao para 'active' acontece apos a troca da
     * senha provisoria em FirstAccessController). Caso contrario, qualquer login
     * efetivamente ativa a conta.
     */
    public function recordLogin(?string $ip = null, ?string $userAgent = null): void
    {
        $oldStatus = $this->status;
        $isPendingFirstAccess = $this->must_change_password === true;

        $resolvedUa = $userAgent ?? request()->userAgent();

        $payload = [
            'last_login_at' => now(),
            'last_login_ip' => $ip ?? request()->ip(),
            'user_agent' => $resolvedUa,
            'last_login_browser' => \App\Support\UserAgentParser::summarize($resolvedUa),
        ];

        if (!$isPendingFirstAccess) {
            $payload['status'] = 'active';
            $payload['active'] = true;
        }

        $this->update($payload);

        if (!$isPendingFirstAccess && $oldStatus !== 'active') {
            UserStatusHistory::logStatusChange($this, $oldStatus, 'active', 'Login realizado');
        }
    }

    /**
     * Historico de mudancas de status
     */
    public function statusHistories(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserStatusHistory::class);
    }

    /**
     * Pedido ativo de troca de e-mail (pending). Latest-of-many porque so
     * um pode estar ativo por vez (regra mantida pelo EmailChangeService).
     */
    public function activeEmailChangeRequest(): HasOne
    {
        return $this->hasOne(EmailChangeRequest::class)
            ->whereNull('used_at')
            ->whereNull('cancelled_at')
            ->latestOfMany();
    }
}
