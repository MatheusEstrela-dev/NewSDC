<?php

namespace App\Observers;

use App\Models\User;
use App\Models\AuditLog;
use App\Models\PermissionAuditLog;
use Illuminate\Support\Facades\Auth;

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
        $relevantFields = ['name', 'email', 'cpf', 'status', 'active', 'orgao_principal_id'];
        $hasRelevantChanges = !empty(array_intersect($changedFields, $relevantFields));

        if ($hasRelevantChanges) {
            AuditLog::logUpdate(
                'users',
                $user->id,
                collect($user->getOriginal())->only($relevantFields)->toArray(),
                collect($user->getAttributes())->only($relevantFields)->toArray()
            );
        }

        if (Auth::check()) {
            PermissionAuditLog::logAction(
                userId: Auth::id(),
                action: PermissionAuditLog::ACTION_USER_UPDATED,
                entityType: 'User',
                entityId: $user->id,
                beforeState: $user->getOriginal(),
                afterState: $user->getAttributes()
            );
        }
    }

    public function deleted(User $user): void
    {
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
}
