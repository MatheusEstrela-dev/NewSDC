<?php

namespace App\Observers;

use App\Models\User;
use App\Models\PermissionAuditLog;
use Illuminate\Support\Facades\Auth;

class UserObserver
{
    public function created(User $user): void
    {
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
