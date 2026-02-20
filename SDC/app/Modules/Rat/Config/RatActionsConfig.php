<?php

declare(strict_types=1);

namespace App\Modules\Rat\Config;

use App\Core\Actions\AbstractModuleActions;
use App\Core\Actions\DTOs\ActionConfigDTO;
use App\Core\Actions\Enums\ActionType;

/**
 * Configuracao de acoes para o modulo RAT.
 */
class RatActionsConfig extends AbstractModuleActions
{
    /**
     * {@inheritdoc}
     */
    public function getModuleName(): string
    {
        return 'rat';
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableActions(): array
    {
        return [
            ActionType::VIEW,
            ActionType::PRINT,
            ActionType::EDIT,
            ActionType::ATTACHMENTS,
            ActionType::DELETE,
            ActionType::EXPORT,
            ActionType::FINALIZE,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultConfig(): array
    {
        return [
            ActionType::VIEW->value => $this->createActionConfig(
                actionType: ActionType::VIEW,
                order: 1
            ),
            ActionType::PRINT->value => $this->createActionConfig(
                actionType: ActionType::PRINT,
                order: 2
            ),
            ActionType::EDIT->value => $this->createActionConfig(
                actionType: ActionType::EDIT,
                order: 3
            ),
            ActionType::ATTACHMENTS->value => $this->createActionConfig(
                actionType: ActionType::ATTACHMENTS,
                order: 4
            ),
            ActionType::DELETE->value => $this->createActionConfig(
                actionType: ActionType::DELETE,
                order: 5
            ),
            ActionType::EXPORT->value => $this->createActionConfig(
                actionType: ActionType::EXPORT,
                enabled: true,
                order: 6
            ),
            ActionType::FINALIZE->value => $this->createActionConfig(
                actionType: ActionType::FINALIZE,
                order: 7
            ),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getConditionalOverrides(mixed $entity = null): array
    {
        if ($entity === null) {
            return [];
        }

        $overrides = [];
        $status = $entity->status ?? null;

        if ($status === 'finalizado') {
            $overrides[ActionType::EDIT->value] = ['enabled' => false];
            $overrides[ActionType::DELETE->value] = ['enabled' => false];
            $overrides[ActionType::FINALIZE->value] = ['enabled' => false];
        }

        if ($status === 'em_andamento') {
            $overrides[ActionType::DELETE->value] = ['enabled' => false];
        }

        if ($status === 'rascunho') {
            $overrides[ActionType::FINALIZE->value] = ['enabled' => false];
        }

        return $overrides;
    }

    /**
     * {@inheritdoc}
     */
    public function canExecute(ActionType $action, mixed $entity): bool
    {
        if ($action === ActionType::FINALIZE) {
            $status = $entity->status ?? null;
            return $status !== 'finalizado' && $status !== 'rascunho';
        }

        return parent::canExecute($action, $entity);
    }
}
