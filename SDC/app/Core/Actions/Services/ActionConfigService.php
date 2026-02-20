<?php

declare(strict_types=1);

namespace App\Core\Actions\Services;

use App\Core\Actions\Contracts\ActionConfigInterface;
use App\Core\Actions\Contracts\ModuleActionsInterface;
use App\Core\Actions\DTOs\ActionConfigDTO;
use App\Core\Actions\Enums\ActionType;
use InvalidArgumentException;

/**
 * Servico principal para gerenciamento de configuracao de acoes.
 * Segue os principios SRP e DIP.
 */
class ActionConfigService implements ActionConfigInterface
{
    /**
     * Configuracoes registradas por modulo.
     *
     * @var array<string, ModuleActionsInterface>
     */
    private array $moduleConfigs = [];

    /**
     * Cache das configuracoes processadas.
     *
     * @var array<string, array<string, ActionConfigDTO>>
     */
    private array $processedConfigs = [];

    /**
     * Registra um modulo com suas configuracoes de acoes.
     *
     * @param ModuleActionsInterface $moduleConfig
     * @return self
     */
    public function registerModule(ModuleActionsInterface $moduleConfig): self
    {
        $moduleName = $moduleConfig->getModuleName();
        $this->moduleConfigs[$moduleName] = $moduleConfig;
        $this->processModuleConfig($moduleName);

        return $this;
    }

    /**
     * Registra multiplos modulos de uma vez.
     *
     * @param array<ModuleActionsInterface> $modules
     * @return self
     */
    public function registerModules(array $modules): self
    {
        foreach ($modules as $module) {
            $this->registerModule($module);
        }

        return $this;
    }

    /**
     * Processa e cacheia a configuracao de um modulo.
     */
    private function processModuleConfig(string $moduleName): void
    {
        $moduleConfig = $this->moduleConfigs[$moduleName];
        $this->processedConfigs[$moduleName] = $moduleConfig->getDefaultConfig();
    }

    /**
     * {@inheritdoc}
     */
    public function getModuleActions(string $module): array
    {
        $this->assertModuleExists($module);

        return $this->processedConfigs[$module];
    }

    /**
     * {@inheritdoc}
     */
    public function isActionEnabled(string $module, ActionType $action): bool
    {
        $config = $this->getActionConfig($module, $action);

        return $config?->enabled ?? false;
    }

    /**
     * {@inheritdoc}
     */
    public function getActionConfig(string $module, ActionType $action): ?ActionConfigDTO
    {
        if (!$this->hasModule($module)) {
            return null;
        }

        return $this->processedConfigs[$module][$action->value] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function toFrontendConfig(): array
    {
        $result = [];

        foreach ($this->processedConfigs as $moduleName => $actions) {
            $result[$moduleName] = [];
            foreach ($actions as $actionKey => $actionConfig) {
                $result[$moduleName][$actionKey] = $actionConfig->toFrontend();
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function hasModule(string $module): bool
    {
        return isset($this->moduleConfigs[$module]);
    }

    /**
     * {@inheritdoc}
     */
    public function getRegisteredModules(): array
    {
        return array_keys($this->moduleConfigs);
    }

    /**
     * Retorna a configuracao de um modulo com overrides condicionais.
     *
     * @param string $module Nome do modulo
     * @param mixed $entity Entidade para aplicar condicoes
     * @return array<string, ActionConfigDTO>
     */
    public function getModuleActionsWithOverrides(string $module, mixed $entity = null): array
    {
        $this->assertModuleExists($module);

        $baseConfig = $this->processedConfigs[$module];
        $moduleHandler = $this->moduleConfigs[$module];
        $overrides = $moduleHandler->getConditionalOverrides($entity);

        if (empty($overrides)) {
            return $baseConfig;
        }

        $result = [];
        foreach ($baseConfig as $actionKey => $actionConfig) {
            if (isset($overrides[$actionKey])) {
                $result[$actionKey] = $actionConfig->with($overrides[$actionKey]);
            } else {
                $result[$actionKey] = $actionConfig;
            }
        }

        return $result;
    }

    /**
     * Retorna configuracao frontend para uma entidade especifica (com overrides).
     *
     * @param string $module Nome do modulo
     * @param mixed $entity Entidade do dominio
     * @return array
     */
    public function toFrontendConfigForEntity(string $module, mixed $entity): array
    {
        $actions = $this->getModuleActionsWithOverrides($module, $entity);
        $result = [];

        foreach ($actions as $actionKey => $actionConfig) {
            $result[$actionKey] = $actionConfig->toFrontend();
        }

        return $result;
    }

    /**
     * Valida se o modulo existe.
     *
     * @throws InvalidArgumentException
     */
    private function assertModuleExists(string $module): void
    {
        if (!$this->hasModule($module)) {
            throw new InvalidArgumentException(
                "Modulo '{$module}' nao registrado. Modulos disponiveis: " .
                implode(', ', $this->getRegisteredModules())
            );
        }
    }

    /**
     * Adiciona ou atualiza uma acao em um modulo existente.
     *
     * @param string $module Nome do modulo
     * @param ActionConfigDTO $actionConfig Configuracao da acao
     * @return self
     */
    public function addAction(string $module, ActionConfigDTO $actionConfig): self
    {
        $this->assertModuleExists($module);
        $this->processedConfigs[$module][$actionConfig->action] = $actionConfig;

        return $this;
    }

    /**
     * Remove uma acao de um modulo.
     *
     * @param string $module Nome do modulo
     * @param ActionType $action Tipo da acao
     * @return self
     */
    public function removeAction(string $module, ActionType $action): self
    {
        $this->assertModuleExists($module);
        unset($this->processedConfigs[$module][$action->value]);

        return $this;
    }

    /**
     * Habilita ou desabilita uma acao.
     *
     * @param string $module Nome do modulo
     * @param ActionType $action Tipo da acao
     * @param bool $enabled Estado desejado
     * @return self
     */
    public function setActionEnabled(string $module, ActionType $action, bool $enabled): self
    {
        $config = $this->getActionConfig($module, $action);

        if ($config) {
            $this->processedConfigs[$module][$action->value] = $config->with(['enabled' => $enabled]);
        }

        return $this;
    }

    /**
     * Limpa o cache de configuracoes processadas.
     *
     * @return self
     */
    public function clearCache(): self
    {
        $this->processedConfigs = [];
        foreach ($this->moduleConfigs as $moduleName => $config) {
            $this->processModuleConfig($moduleName);
        }

        return $this;
    }
}
