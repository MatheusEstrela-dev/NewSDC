<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\PermissionRegistrar;

/**
 * Job para limpeza de roles e permissions expiradas.
 * Executa periodicamente para remover atribuicoes que passaram do expires_at.
 */
class CleanExpiredPermissions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
    }

    /**
     * Executa a limpeza de permissoes expiradas.
     */
    public function handle(): void
    {
        $rolesRemoved = $this->cleanExpiredRoles();
        $permissionsRemoved = $this->cleanExpiredPermissions();

        if ($rolesRemoved > 0 || $permissionsRemoved > 0) {
            $this->clearPermissionCache();

            Log::info('CleanExpiredPermissions: Limpeza concluida', [
                'roles_removed' => $rolesRemoved,
                'permissions_removed' => $permissionsRemoved,
            ]);
        }
    }

    /**
     * Remove roles expiradas da tabela model_has_roles.
     */
    protected function cleanExpiredRoles(): int
    {
        return DB::table('model_has_roles')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->delete();
    }

    /**
     * Remove permissions expiradas da tabela model_has_permissions.
     */
    protected function cleanExpiredPermissions(): int
    {
        return DB::table('model_has_permissions')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->delete();
    }

    /**
     * Limpa o cache de permissoes do Spatie.
     */
    protected function clearPermissionCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
