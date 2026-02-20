<?php

declare(strict_types=1);

namespace App\Core\Actions\Policies;

use App\Core\Actions\Contracts\ActionConfigInterface;
use App\Core\Actions\Contracts\ModuleActionsInterface;
use App\Core\Actions\Enums\ActionType;
use App\Core\Actions\Services\ActionConfigService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;

/**
 * Policy de seguranca para acoes.
 * Implementa o Server-side Enforcement - a fonte da verdade para Gate::allows().
 */
class ActionPolicy
{
    public function __construct(
        private readonly ActionConfigService $actionConfigService
    ) {}

    /**
     * Verifica se o usuario pode executar uma acao em um modulo.
     */
    public function canExecute(
        ?Authenticatable $user,
        string $module,
        ActionType $action,
        mixed $entity = null
    ): bool {
        if (!$user) {
            return false;
        }

        if ($this->isSuperAdmin($user)) {
            return true;
        }

        $actionConfig = $this->actionConfigService->getActionConfig($module, $action);

        if (!$actionConfig) {
            return false;
        }

        if (!$actionConfig->enabled) {
            return false;
        }

        if (!$this->checkPermission($user, $actionConfig->permission)) {
            return false;
        }

        if (!$this->checkLevel($user, $actionConfig->requiredLevel)) {
            return false;
        }

        if ($entity !== null && !$this->checkEntityConditions($module, $action, $entity)) {
            return false;
        }

        return true;
    }

    /**
     * Verifica se o usuario e Super Admin.
     */
    private function isSuperAdmin(Authenticatable $user): bool
    {
        return $user->is_super_admin ?? false;
    }

    /**
     * Verifica permissao do usuario.
     */
    private function checkPermission(Authenticatable $user, ?string $permission): bool
    {
        if (empty($permission)) {
            return true;
        }

        $userPermissions = $user->permissions ?? [];

        return in_array($permission, $userPermissions, true);
    }

    /**
     * Verifica nivel do usuario.
     */
    private function checkLevel(Authenticatable $user, ?int $requiredLevel): bool
    {
        if ($requiredLevel === null) {
            return true;
        }

        $userLevel = $this->getUserLevel($user);

        return $userLevel <= $requiredLevel;
    }

    /**
     * Obtem o nivel do usuario baseado no cargo.
     */
    private function getUserLevel(Authenticatable $user): int
    {
        $role = $user->role ?? $user->cargo ?? 'user';
        $levels = config('permissions.levels', []);

        return $levels[$role] ?? config('permissions.default_level', 99);
    }

    /**
     * Verifica condicoes baseadas na entidade.
     */
    private function checkEntityConditions(string $module, ActionType $action, mixed $entity): bool
    {
        if (!$this->actionConfigService->hasModule($module)) {
            return true;
        }

        $moduleConfig = $this->getModuleConfig($module);

        if ($moduleConfig && method_exists($moduleConfig, 'canExecute')) {
            return $moduleConfig->canExecute($action, $entity);
        }

        return true;
    }

    /**
     * Obtem a configuracao do modulo.
     */
    private function getModuleConfig(string $module): ?ModuleActionsInterface
    {
        $reflection = new \ReflectionClass($this->actionConfigService);
        $property = $reflection->getProperty('moduleConfigs');
        $property->setAccessible(true);
        $configs = $property->getValue($this->actionConfigService);

        return $configs[$module] ?? null;
    }

    /**
     * Registra os gates para todas as acoes de todos os modulos.
     */
    public function registerGates(): void
    {
        foreach ($this->actionConfigService->getRegisteredModules() as $module) {
            foreach (ActionType::cases() as $action) {
                $gateName = "{$module}.{$action->value}";

                Gate::define($gateName, function ($user, $entity = null) use ($module, $action) {
                    return $this->canExecute($user, $module, $action, $entity);
                });
            }
        }
    }

    /**
     * Retorna todas as acoes permitidas para o usuario em um modulo.
     */
    public function getAllowedActions(Authenticatable $user, string $module, mixed $entity = null): array
    {
        $allowed = [];

        foreach (ActionType::cases() as $action) {
            if ($this->canExecute($user, $module, $action, $entity)) {
                $allowed[] = $action->value;
            }
        }

        return $allowed;
    }

    /**
     * Helper para verificar acao rapidamente.
     */
    public static function check(string $module, string $action, mixed $entity = null): bool
    {
        $policy = app(self::class);
        $user = auth()->user();
        $actionType = ActionType::tryFrom($action);

        if (!$actionType) {
            return false;
        }

        return $policy->canExecute($user, $module, $actionType, $entity);
    }
}
