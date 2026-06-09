<?php

namespace App\Observers;

use App\Models\User;
use App\Models\AuditLog;
use App\Models\PermissionAuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class UserObserver
{
    public function creating(User $user): void
    {
        if (Auth::check() && !$user->created_by) {
            $user->created_by = Auth::id();
        }
    }

    public function updating(User $user): void
    {
        if (Auth::check()) {
            $user->updated_by = Auth::id();
        }
    }

    public function created(User $user): void
    {
        AuditLog::logInsert('users', $user->id, $user->only([
            'id', 'name', 'email', 'cpf', 'status', 'active'
        ]));

        if (Auth::check()) {
            PermissionAuditLog::logAction(
                userId: Auth::id(),
                action: PermissionAuditLog::ACTION_USER_CREATED,
                entityType: 'User',
                entityId: $user->id,
                afterState: $user->only(['id', 'name', 'email', 'cpf'])
            );
        }
    }

    public function updated(User $user): void
    {
        $changedFields = array_keys($user->getChanges());
        $relevantFields = ['name', 'email', 'cpf', 'status', 'active', 'orgao_principal_id', 'password'];
        $hasRelevantChanges = !empty(array_intersect($changedFields, $relevantFields));

        // Invalida o cache de login por CPF (LoginRequest) quando muda algo que
        // afeta a decisao de autenticacao. Sem isso, senha antiga ou usuario
        // recem-bloqueado continuariam validos pela janela do cache.
        if (!empty(array_intersect($changedFields, ['cpf', 'status', 'active', 'password']))) {
            $this->forgetCpfLoginCache($user);
        }

        if ($hasRelevantChanges) {
            $old = collect($user->getOriginal())->only($relevantFields)->toArray();
            $new = collect($user->getAttributes())->only($relevantFields)->toArray();

            if (array_key_exists('password', $old)) {
                $old['password'] = '***';
            }
            if (array_key_exists('password', $new)) {
                $new['password'] = '***';
            }

            AuditLog::logUpdate('users', $user->id, $old, $new);
        }

        if (Auth::check()) {
            $before = collect($user->getOriginal())->except(['password', 'remember_token'])->toArray();
            $after = collect($user->getAttributes())->except(['password', 'remember_token'])->toArray();

            PermissionAuditLog::logAction(
                userId: Auth::id(),
                action: PermissionAuditLog::ACTION_USER_UPDATED,
                entityType: 'User',
                entityId: $user->id,
                beforeState: $before,
                afterState: $after
            );
        }
    }

    public function deleted(User $user): void
    {
        $this->forgetCpfLoginCache($user);

        AuditLog::logDelete('users', $user->id, $user->only([
            'id', 'name', 'email', 'cpf', 'status', 'active'
        ]));

        if (Auth::check()) {
            PermissionAuditLog::logAction(
                userId: Auth::id(),
                action: PermissionAuditLog::ACTION_USER_DELETED,
                entityType: 'User',
                entityId: $user->id,
                beforeState: $user->only(['id', 'name', 'email', 'cpf'])
            );
        }
    }

    /**
     * Esquece o cache de login por CPF (LoginRequest::authenticate). Limpa tanto
     * o CPF atual quanto o original (caso o CPF em si tenha mudado).
     */
    private function forgetCpfLoginCache(User $user): void
    {
        foreach (array_unique(array_filter([
            $user->getOriginal('cpf'),
            $user->cpf,
        ])) as $cpf) {
            Cache::forget("user:cpf:{$cpf}");
        }
    }
}
