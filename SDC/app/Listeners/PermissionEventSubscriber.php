<?php

namespace App\Listeners;

use App\Models\PermissionAuditLog;
use Illuminate\Events\Dispatcher;
use Spatie\Permission\Contracts\Permission;
use Spatie\Permission\Contracts\Role;

/**
 * Subscriber para eventos do Spatie Permission.
 * Registra automaticamente no audit log todas as mudancas de roles/permissions.
 */
class PermissionEventSubscriber
{
    /**
     * Registra role atribuida a um model (usuario).
     */
    public function handleRoleAttached($event): void
    {
        $this->logAudit(
            PermissionAuditLog::ACTION_ROLE_ASSIGNED,
            'user',
            $this->getModelId($event),
            null,
            [
                'role_id' => $this->getRoleId($event),
                'role_name' => $this->getRoleName($event),
            ]
        );
    }

    /**
     * Registra role removida de um model (usuario).
     */
    public function handleRoleDetached($event): void
    {
        $this->logAudit(
            PermissionAuditLog::ACTION_ROLE_REMOVED,
            'user',
            $this->getModelId($event),
            [
                'role_id' => $this->getRoleId($event),
                'role_name' => $this->getRoleName($event),
            ],
            null
        );
    }

    /**
     * Registra permissao atribuida diretamente a um model.
     */
    public function handlePermissionAttached($event): void
    {
        $this->logAudit(
            PermissionAuditLog::ACTION_PERMISSION_ASSIGNED,
            'user',
            $this->getModelId($event),
            null,
            [
                'permission_id' => $this->getPermissionId($event),
                'permission_name' => $this->getPermissionName($event),
            ]
        );
    }

    /**
     * Registra permissao removida diretamente de um model.
     */
    public function handlePermissionDetached($event): void
    {
        $this->logAudit(
            PermissionAuditLog::ACTION_PERMISSION_REMOVED,
            'user',
            $this->getModelId($event),
            [
                'permission_id' => $this->getPermissionId($event),
                'permission_name' => $this->getPermissionName($event),
            ],
            null
        );
    }

    /**
     * Registra permissao atribuida a uma role.
     */
    public function handlePermissionAttachedToRole($event): void
    {
        $this->logAudit(
            PermissionAuditLog::ACTION_PERMISSION_ASSIGNED,
            'role',
            $this->getRoleId($event),
            null,
            [
                'permission_id' => $this->getPermissionId($event),
                'permission_name' => $this->getPermissionName($event),
            ]
        );
    }

    /**
     * Registra permissao removida de uma role.
     */
    public function handlePermissionDetachedFromRole($event): void
    {
        $this->logAudit(
            PermissionAuditLog::ACTION_PERMISSION_REMOVED,
            'role',
            $this->getRoleId($event),
            [
                'permission_id' => $this->getPermissionId($event),
                'permission_name' => $this->getPermissionName($event),
            ],
            null
        );
    }

    /**
     * Registra a acao no audit log.
     */
    protected function logAudit(
        string $action,
        string $entityType,
        ?int $entityId,
        ?array $beforeState,
        ?array $afterState
    ): void {
        $userId = auth()->id();

        if (!$userId) {
            return;
        }

        PermissionAuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'before_state' => $beforeState,
            'after_state' => $afterState,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Extrai o ID do model do evento.
     */
    protected function getModelId($event): ?int
    {
        if (isset($event->model)) {
            return $event->model->id ?? null;
        }

        if (is_object($event) && property_exists($event, 'user')) {
            return $event->user->id ?? null;
        }

        return null;
    }

    /**
     * Extrai o ID da role do evento.
     */
    protected function getRoleId($event): ?int
    {
        if (isset($event->role)) {
            return $event->role instanceof Role ? $event->role->id : null;
        }

        if (isset($event->roles) && is_array($event->roles)) {
            $firstRole = reset($event->roles);
            return $firstRole instanceof Role ? $firstRole->id : null;
        }

        return null;
    }

    /**
     * Extrai o nome da role do evento.
     */
    protected function getRoleName($event): ?string
    {
        if (isset($event->role)) {
            return $event->role instanceof Role ? $event->role->name : null;
        }

        if (isset($event->roles) && is_array($event->roles)) {
            $firstRole = reset($event->roles);
            return $firstRole instanceof Role ? $firstRole->name : null;
        }

        return null;
    }

    /**
     * Extrai o ID da permission do evento.
     */
    protected function getPermissionId($event): ?int
    {
        if (isset($event->permission)) {
            return $event->permission instanceof Permission ? $event->permission->id : null;
        }

        if (isset($event->permissions) && is_array($event->permissions)) {
            $firstPermission = reset($event->permissions);
            return $firstPermission instanceof Permission ? $firstPermission->id : null;
        }

        return null;
    }

    /**
     * Extrai o nome da permission do evento.
     */
    protected function getPermissionName($event): ?string
    {
        if (isset($event->permission)) {
            return $event->permission instanceof Permission ? $event->permission->name : null;
        }

        if (isset($event->permissions) && is_array($event->permissions)) {
            $firstPermission = reset($event->permissions);
            return $firstPermission instanceof Permission ? $firstPermission->name : null;
        }

        return null;
    }

    /**
     * Registra os listeners nos eventos do Spatie Permission.
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            'Spatie\Permission\Events\RoleAttached' => 'handleRoleAttached',
            'Spatie\Permission\Events\RoleDetached' => 'handleRoleDetached',
            'Spatie\Permission\Events\PermissionAttached' => 'handlePermissionAttached',
            'Spatie\Permission\Events\PermissionDetached' => 'handlePermissionDetached',
        ];
    }
}
