<?php

declare(strict_types=1);

namespace App\Core\Actions\Contracts;

use App\Core\Actions\DTOs\ActionConfigDTO;
use App\Core\Actions\Enums\ActionType;

/**
 * Interface para o servico de configuracao de acoes.
 * Segue o principio ISP (Interface Segregation) - interface especifica para consulta de configuracoes.
 */
interface ActionConfigInterface
{
    /**
     * Retorna todas as acoes configuradas para um modulo.
     *
     * @param string $module Nome do modulo (ex: 'rat', 'pae', 'orgaos')
     * @return array<string, ActionConfigDTO>
     */
    public function getModuleActions(string $module): array;

    /**
     * Verifica se uma acao esta habilitada para um modulo.
     *
     * @param string $module Nome do modulo
     * @param ActionType $action Tipo da acao
     * @return bool
     */
    public function isActionEnabled(string $module, ActionType $action): bool;

    /**
     * Retorna a configuracao de uma acao especifica.
     *
     * @param string $module Nome do modulo
     * @param ActionType $action Tipo da acao
     * @return ActionConfigDTO|null
     */
    public function getActionConfig(string $module, ActionType $action): ?ActionConfigDTO;

    /**
     * Retorna todas as configuracoes em formato para o frontend.
     *
     * @return array<string, array>
     */
    public function toFrontendConfig(): array;

    /**
     * Verifica se um modulo esta registrado.
     *
     * @param string $module Nome do modulo
     * @return bool
     */
    public function hasModule(string $module): bool;

    /**
     * Retorna a lista de modulos registrados.
     *
     * @return array<string>
     */
    public function getRegisteredModules(): array;
}
