<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Representa uma organização/unidade no modelo de multi-tenancy.
 *
 * Cada tenant pode ter seu próprio banco de dados (multi-db) ou
 * compartilhar o banco principal com escopo por tenant_id (single-db).
 *
 * O banco ativo em runtime é configurado pelo SetTenant middleware.
 *
 * @property int         $id
 * @property string      $nome
 * @property string      $slug
 * @property string|null $database
 * @property string|null $dominio
 * @property bool        $ativo
 * @property array|null  $config
 */
class Tenant extends Model
{
    use SoftDeletes;

    protected $table = 'tenants';

    protected $fillable = [
        'nome',
        'slug',
        'database',
        'dominio',
        'ativo',
        'config',
    ];

    protected $casts = [
        'ativo'  => 'boolean',
        'config' => 'array',
    ];

    /**
     * Usuários que pertencem a este tenant.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Nome da conexão de banco do tenant.
     *
     * @deprecated Modelo A (single-DB): a tenancy é isolada por tenant_id (trait
     * HasTenant), não por banco. Trocar a conexão por request mutava config
     * global (`database.connections.tenancy.database`) e vazava entre coroutines
     * concorrentes no Swoole. Sempre retorna a conexão padrão; a coluna
     * `database` deixou de comutar conexão.
     */
    public function getDatabaseConnection(): string
    {
        return config('database.default');
    }

    /**
     * Resolve tenant pelo slug da request ou pelo usuário autenticado.
     */
    public static function resolveFromRequest(\Illuminate\Http\Request $request): ?self
    {
        // 1. Por header (API: X-Tenant)
        if ($slug = $request->header('X-Tenant')) {
            return static::where('slug', $slug)->where('ativo', true)->first();
        }

        // 2. Pelo subdomínio (ex: compdec.newsdc.gov.br)
        $host = $request->getHost();
        $tenant = static::where('dominio', $host)->where('ativo', true)->first();
        if ($tenant) {
            return $tenant;
        }

        // 3. Pelo tenant_id do usuário autenticado
        if ($user = $request->user()) {
            $tenantId = $user->tenant_id ?? null;
            if ($tenantId) {
                return static::find($tenantId);
            }
        }

        return null;
    }
}
