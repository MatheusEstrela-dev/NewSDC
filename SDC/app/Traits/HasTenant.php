<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Tenant;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait HasTenant — adiciona escopo de multi-tenancy a qualquer Model.
 *
 * Uso:
 *   class MinhaEntidade extends Model {
 *       use HasTenant;
 *   }
 *
 * Requer coluna `tenant_id` na tabela (nullable para compatibilidade com
 * registros globais/sistema sem tenant específico).
 *
 * Quando um tenant estiver ativo no contexto da request (TenantContext, seguro
 * para coroutine), todas as queries automaticamente filtram por tenant_id.
 */
trait HasTenant
{
    /**
     * Registra o GlobalScope de tenancy ao bootar o model.
     */
    protected static function bootHasTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $query) {
            $tenant = TenantContext::get();

            if ($tenant instanceof Tenant) {
                $query->where($query->getModel()->getTable() . '.tenant_id', $tenant->id);

                return;
            }

            // Fail-closed: SEM tenant no contexto, nao retorna NADA (em vez de
            // todos os tenants). O default permissivo vazaria dados entre tenants
            // em qualquer caminho sem SetTenant (job de fila, comando console,
            // task worker, codigo futuro). Acesso cross-tenant/global e EXPLICITO
            // via scopeSemFiltroTenant().
            $query->whereRaw('1 = 0');
        });

        // Preenche tenant_id automaticamente ao criar.
        static::creating(function ($model) {
            $tenant = TenantContext::get();

            if ($tenant instanceof Tenant) {
                if (empty($model->tenant_id)) {
                    $model->tenant_id = $tenant->id;
                }

                return;
            }

            // Fail-closed na escrita (simetrico ao scope de leitura): criar SEM
            // tenant no contexto e sem tenant_id explicito geraria uma linha orfa
            // (tenant_id null) invisivel para qualquer leitura tenant-scoped --
            // um vazamento silencioso de dados "perdidos". Aborta alto e claro.
            // Criacao cross-tenant/sistema deve atribuir tenant_id ANTES de salvar.
            if (empty($model->tenant_id)) {
                throw new \RuntimeException(sprintf(
                    'HasTenant: tentativa de criar %s sem tenant no contexto. '
                    .'Defina o TenantContext (via SetTenant) ou atribua tenant_id explicitamente.',
                    $model::class
                ));
            }
        });
    }

    /**
     * Relacionamento com o Tenant dono deste registro.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope para buscar além do tenant atual (acesso cross-tenant para super-admin).
     */
    public function scopeSemFiltroTenant(Builder $query): Builder
    {
        return $query->withoutGlobalScope('tenant');
    }
}
