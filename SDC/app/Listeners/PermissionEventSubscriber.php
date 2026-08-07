<?php

namespace App\Listeners;

use App\Models\PermissionAuditLog;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Contracts\Permission;
use Spatie\Permission\Contracts\Role;
use Spatie\Permission\PermissionRegistrar;

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
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        $userId = $user?->id;

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
        return $this->resolverRole($event)['id'];
    }

    /**
     * Extrai o nome da role do evento.
     */
    protected function getRoleName($event): ?string
    {
        return $this->resolverRole($event)['name'];
    }

    /**
     * Extrai o ID da permission do evento.
     */
    protected function getPermissionId($event): ?int
    {
        return $this->resolverPermissao($event)['id'];
    }

    /**
     * Extrai o nome da permission do evento.
     */
    protected function getPermissionName($event): ?string
    {
        return $this->resolverPermissao($event)['name'];
    }

    /**
     * Descobre qual role o evento carrega.
     *
     * O Spatie entrega a role em $event->rolesOrIds, e o proprio docblock dele
     * avisa que ali pode vir id, nome, objeto Role ou Collection, conforme o
     * caminho que disparou o evento. Ler $event->role ou $event->roles, que nao
     * existem em nenhuma versao, resultava em role_id e role_name sempre nulos:
     * a trilha registrava que alguem recebeu "um cargo", sem dizer qual.
     *
     * @return array{id: int|null, name: string|null}
     */
    protected function resolverRole($event): array
    {
        $bruto = $this->primeiroDoEvento($event, ['rolesOrIds', 'role', 'roles']);

        if ($bruto instanceof Role) {
            return ['id' => $bruto->id, 'name' => $bruto->name];
        }

        if ($bruto === null) {
            return ['id' => null, 'name' => null];
        }

        $modelo = app(PermissionRegistrar::class)->getRoleClass();

        $encontrada = is_numeric($bruto)
            ? $modelo::find($bruto)
            : $modelo::where('name', $bruto)->orWhere('slug', $bruto)->first();

        return ['id' => $encontrada?->id, 'name' => $encontrada?->name];
    }

    /**
     * Mesma logica para permissao, que chega em $event->permissionsOrIds.
     *
     * @return array{id: int|null, name: string|null}
     */
    protected function resolverPermissao($event): array
    {
        $bruto = $this->primeiroDoEvento($event, ['permissionsOrIds', 'permission', 'permissions']);

        if ($bruto instanceof Permission) {
            return ['id' => $bruto->id, 'name' => $bruto->name];
        }

        if ($bruto === null) {
            return ['id' => null, 'name' => null];
        }

        $modelo = app(PermissionRegistrar::class)->getPermissionClass();

        $encontrada = is_numeric($bruto)
            ? $modelo::find($bruto)
            : $modelo::where('name', $bruto)->first();

        return ['id' => $encontrada?->id, 'name' => $encontrada?->name];
    }

    /**
     * Le a primeira das propriedades informadas que exista no evento e devolve
     * seu primeiro elemento, achatando array e Collection.
     */
    protected function primeiroDoEvento($event, array $propriedades): mixed
    {
        foreach ($propriedades as $propriedade) {
            if (! isset($event->{$propriedade})) {
                continue;
            }

            $valor = $event->{$propriedade};

            if ($valor instanceof Collection) {
                $valor = $valor->all();
            }

            if (is_array($valor)) {
                $valor = reset($valor);
            }

            if ($valor !== false && $valor !== null) {
                return $valor;
            }
        }

        return null;
    }

    /**
     * Eventos do Spatie que alimentam a trilha.
     *
     * So sao disparados com permission.events_enabled = true.
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
