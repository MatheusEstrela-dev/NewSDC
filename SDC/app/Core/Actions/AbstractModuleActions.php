<?php

declare(strict_types=1);

namespace App\Core\Actions;

use App\Core\Actions\Contracts\ModuleActionsInterface;
use App\Core\Actions\DTOs\ActionConfigDTO;
use App\Core\Actions\Enums\ActionType;

/**
 * Classe abstrata base para configuracao de acoes de modulos.
 * Fornece implementacao padrao seguindo DRY.
 */
abstract class AbstractModuleActions implements ModuleActionsInterface
{
    /**
     * {@inheritdoc}
     */
    abstract public function getModuleName(): string;

    /**
     * {@inheritdoc}
     */
    public function getAvailableActions(): array
    {
        return ActionType::defaultCrudActions();
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultConfig(): array
    {
        $configs = [];
        $prefix = $this->getPermissionPrefix();

        foreach ($this->getAvailableActions() as $actionType) {
            $permission = $this->buildPermission($prefix, $actionType);
            $configs[$actionType->value] = ActionConfigDTO::fromActionType(
                actionType: $actionType,
                enabled: true,
                permission: $permission
            );
        }

        return $configs;
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissionPrefix(): string
    {
        return $this->getModuleName();
    }

    /**
     * {@inheritdoc}
     */
    public function getActionsRequiringConfirmation(): array
    {
        return [ActionType::DELETE, ActionType::ARCHIVE];
    }

    /**
     * {@inheritdoc}
     */
    public function getConditionalOverrides(mixed $entity = null): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function canExecute(ActionType $action, mixed $entity): bool
    {
        $overrides = $this->getConditionalOverrides($entity);
        $actionKey = $action->value;

        if (isset($overrides[$actionKey]['enabled']) && $overrides[$actionKey]['enabled'] === false) {
            return false;
        }

        return true;
    }

    /**
     * Constroi o nome da permissao baseado no prefixo e tipo de acao.
     */
    protected function buildPermission(string $prefix, ActionType $actionType): string
    {
        $actionPermissionMap = [
            ActionType::VIEW->value => 'view',
            ActionType::PRINT->value => 'view',
            ActionType::EDIT->value => 'edit',
            ActionType::DELETE->value => 'delete',
            ActionType::ATTACHMENTS->value => 'edit',
            ActionType::HISTORY->value => 'view',
            ActionType::ARCHIVE->value => 'archive',
            ActionType::UPLOAD->value => 'edit',
            ActionType::EXPORT->value => 'export',
            ActionType::DUPLICATE->value => 'create',
        ];

        $permissionSuffix = $actionPermissionMap[$actionType->value] ?? $actionType->value;

        return "{$prefix}.{$permissionSuffix}";
    }

    /**
     * Helper para criar configuracao customizada de uma acao.
     */
    protected function createActionConfig(
        ActionType $actionType,
        bool $enabled = true,
        ?string $permission = null,
        ?string $label = null,
        ?string $variant = null,
        ?int $order = null
    ): ActionConfigDTO {
        return new ActionConfigDTO(
            action: $actionType->value,
            enabled: $enabled,
            permission: $permission ?? $this->buildPermission($this->getPermissionPrefix(), $actionType),
            icon: $actionType->icon(),
            variant: $variant ?? $actionType->variant(),
            label: $label ?? $actionType->label(),
            order: $order,
            requiresConfirmation: in_array($actionType, $this->getActionsRequiringConfirmation(), true),
            confirmationMessage: $actionType === ActionType::DELETE
                ? 'Tem certeza que deseja excluir este registro?'
                : null
        );
    }
}
