<?php

namespace App\Observers;

use App\Models\Role;
use App\Models\PermissionAuditLog;
use Illuminate\Support\Facades\Auth;

class RoleObserver
{
    public function created(Role $role): void
    {
        if (Auth::check()) {
            PermissionAuditLog::logAction(
                userId: Auth::id(),
                action: PermissionAuditLog::ACTION_ROLE_CREATED,
                entityType: 'Role',
                entityId: $role->id,
                afterState: $role->only(['id', 'name', 'slug', 'description'])
            );
        }
    }

    public function updated(Role $role): void
    {
        if (Auth::check()) {
            PermissionAuditLog::logAction(
                userId: Auth::id(),
                action: PermissionAuditLog::ACTION_ROLE_UPDATED,
                entityType: 'Role',
                entityId: $role->id,
                beforeState: $role->getOriginal(),
                afterState: $role->getAttributes()
            );
        }
    }

    public function deleted(Role $role): void
    {
        if (Auth::check()) {
            PermissionAuditLog::logAction(
                userId: Auth::id(),
                action: PermissionAuditLog::ACTION_ROLE_DELETED,
                entityType: 'Role',
                entityId: $role->id,
                beforeState: $role->only(['id', 'name', 'slug'])
            );
        }
    }
}
