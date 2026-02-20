<?php

declare(strict_types=1);

namespace App\Core\Actions\DTOs;

use App\Core\Actions\Enums\ActionType;
use InvalidArgumentException;

/**
 * Data Transfer Object para configuracao de acoes.
 * Segue o principio SRP (Single Responsibility) - apenas transfere dados de configuracao.
 */
final class ActionConfigDTO
{
    public function __construct(
        public readonly string $action,
        public readonly bool $enabled = true,
        public readonly ?string $permission = null,
        public readonly ?string $icon = null,
        public readonly ?string $variant = null,
        public readonly ?string $label = null,
        public readonly ?int $order = null,
        public readonly ?string $tooltip = null,
        public readonly bool $requiresConfirmation = false,
        public readonly ?string $confirmationMessage = null,
        public readonly ?int $requiredLevel = null,
        public readonly array $metadata = [],
        public readonly ?string $route = null,
        public readonly ?array $modalConfig = null
    ) {
        $this->validateAction();
    }

    /**
     * Valida se a acao e um tipo valido.
     */
    private function validateAction(): void
    {
        $validActions = ActionType::values();
        if (!in_array($this->action, $validActions, true)) {
            throw new InvalidArgumentException(
                "Acao '{$this->action}' invalida. Acoes validas: " . implode(', ', $validActions)
            );
        }
    }

    /**
     * Cria um DTO a partir de um array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            action: $data['action'] ?? throw new InvalidArgumentException('Campo action e obrigatorio'),
            enabled: $data['enabled'] ?? true,
            permission: $data['permission'] ?? null,
            icon: $data['icon'] ?? null,
            variant: $data['variant'] ?? null,
            label: $data['label'] ?? null,
            order: $data['order'] ?? null,
            tooltip: $data['tooltip'] ?? null,
            requiresConfirmation: $data['requires_confirmation'] ?? false,
            confirmationMessage: $data['confirmation_message'] ?? null,
            requiredLevel: $data['required_level'] ?? null,
            metadata: $data['metadata'] ?? [],
            route: $data['route'] ?? null,
            modalConfig: $data['modal_config'] ?? null
        );
    }

    /**
     * Cria um DTO a partir de um ActionType com valores padrao.
     */
    public static function fromActionType(
        ActionType $actionType,
        bool $enabled = true,
        ?string $permission = null,
        ?int $requiredLevel = null
    ): self {
        return new self(
            action: $actionType->value,
            enabled: $enabled,
            permission: $permission,
            icon: $actionType->icon(),
            variant: $actionType->variant(),
            label: $actionType->label(),
            requiresConfirmation: in_array($actionType, [
                ActionType::DELETE,
                ActionType::ARCHIVE,
                ActionType::FINALIZE,
            ], true),
            confirmationMessage: match ($actionType) {
                ActionType::DELETE => 'Tem certeza que deseja excluir este registro?',
                ActionType::ARCHIVE => 'Tem certeza que deseja arquivar este registro?',
                ActionType::FINALIZE => 'Tem certeza que deseja finalizar este registro? Esta acao nao pode ser desfeita.',
                default => null
            },
            requiredLevel: $requiredLevel
        );
    }

    /**
     * Cria um DTO a partir do config/permissions.php.
     */
    public static function fromPermissionsConfig(
        ActionType $actionType,
        string $module,
        string $group
    ): self {
        $permissionSlug = config("permissions.modules.{$module}.{$group}.{$actionType->value}");
        $levelMap = self::getActionLevelMap();

        return new self(
            action: $actionType->value,
            enabled: !empty($permissionSlug),
            permission: $permissionSlug,
            icon: $actionType->icon(),
            variant: $actionType->variant(),
            label: $actionType->label(),
            requiredLevel: $levelMap[$actionType->value] ?? null,
            requiresConfirmation: in_array($actionType, [
                ActionType::DELETE,
                ActionType::ARCHIVE,
                ActionType::FINALIZE,
            ], true),
            confirmationMessage: match ($actionType) {
                ActionType::DELETE => 'Tem certeza que deseja excluir este registro?',
                ActionType::ARCHIVE => 'Tem certeza que deseja arquivar este registro?',
                ActionType::FINALIZE => 'Tem certeza que deseja finalizar este registro? Esta acao nao pode ser desfeita.',
                default => null
            }
        );
    }

    /**
     * Mapeamento de nivel minimo por acao.
     * Baseado em config/permissions.php levels.
     */
    private static function getActionLevelMap(): array
    {
        return [
            'view' => config('permissions.levels.viewer', 5),
            'print' => config('permissions.levels.viewer', 5),
            'create' => config('permissions.levels.operator', 4),
            'edit' => config('permissions.levels.analyst', 3),
            'delete' => config('permissions.levels.manager', 2),
            'archive' => config('permissions.levels.manager', 2),
            'export' => config('permissions.levels.analyst', 3),
            'approve' => config('permissions.levels.manager', 2),
            'finalize' => config('permissions.levels.manager', 2),
        ];
    }

    /**
     * Converte o DTO para array.
     */
    public function toArray(): array
    {
        return [
            'action' => $this->action,
            'enabled' => $this->enabled,
            'permission' => $this->permission,
            'icon' => $this->icon,
            'variant' => $this->variant,
            'label' => $this->label,
            'order' => $this->order,
            'tooltip' => $this->tooltip,
            'requires_confirmation' => $this->requiresConfirmation,
            'confirmation_message' => $this->confirmationMessage,
            'required_level' => $this->requiredLevel,
            'metadata' => $this->metadata,
            'route' => $this->route,
            'modal_config' => $this->modalConfig,
        ];
    }

    /**
     * Converte o DTO para formato frontend.
     */
    public function toFrontend(): array
    {
        return [
            'enabled' => $this->enabled,
            'permission' => $this->permission,
            'icon' => $this->icon,
            'variant' => $this->variant,
            'label' => $this->label,
            'order' => $this->order,
            'tooltip' => $this->tooltip,
            'requiresConfirmation' => $this->requiresConfirmation,
            'confirmationMessage' => $this->confirmationMessage,
            'requiredLevel' => $this->requiredLevel,
            'metadata' => $this->metadata,
            'route' => $this->route,
            'modalConfig' => $this->modalConfig,
        ];
    }

    /**
     * Retorna uma copia do DTO com valores alterados.
     */
    public function with(array $overrides): self
    {
        return new self(
            action: $overrides['action'] ?? $this->action,
            enabled: $overrides['enabled'] ?? $this->enabled,
            permission: array_key_exists('permission', $overrides) ? $overrides['permission'] : $this->permission,
            icon: array_key_exists('icon', $overrides) ? $overrides['icon'] : $this->icon,
            variant: array_key_exists('variant', $overrides) ? $overrides['variant'] : $this->variant,
            label: array_key_exists('label', $overrides) ? $overrides['label'] : $this->label,
            order: array_key_exists('order', $overrides) ? $overrides['order'] : $this->order,
            tooltip: array_key_exists('tooltip', $overrides) ? $overrides['tooltip'] : $this->tooltip,
            requiresConfirmation: $overrides['requires_confirmation'] ?? $this->requiresConfirmation,
            confirmationMessage: array_key_exists('confirmation_message', $overrides)
                ? $overrides['confirmation_message']
                : $this->confirmationMessage,
            requiredLevel: array_key_exists('required_level', $overrides)
                ? $overrides['required_level']
                : $this->requiredLevel,
            metadata: array_key_exists('metadata', $overrides)
                ? array_merge($this->metadata, $overrides['metadata'])
                : $this->metadata,
            route: array_key_exists('route', $overrides) ? $overrides['route'] : $this->route,
            modalConfig: array_key_exists('modal_config', $overrides)
                ? $overrides['modal_config']
                : $this->modalConfig
        );
    }

    /**
     * Verifica se a acao e do tipo especificado.
     */
    public function isType(ActionType $type): bool
    {
        return $this->action === $type->value;
    }

    /**
     * Verifica se o usuario tem nivel suficiente para executar a acao.
     */
    public function userHasRequiredLevel(int $userLevel): bool
    {
        if ($this->requiredLevel === null) {
            return true;
        }

        return $userLevel <= $this->requiredLevel;
    }

    /**
     * Adiciona metadata ao DTO.
     */
    public function withMetadata(string $key, mixed $value): self
    {
        $newMetadata = $this->metadata;
        $newMetadata[$key] = $value;

        return $this->with(['metadata' => $newMetadata]);
    }

    /**
     * Define configuracao de modal para a acao.
     */
    public function withModalConfig(array $config): self
    {
        return $this->with(['modal_config' => $config]);
    }

    /**
     * Define uma rota customizada para a acao.
     */
    public function withRoute(string $route): self
    {
        return $this->with(['route' => $route]);
    }
}
