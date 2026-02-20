<?php

declare(strict_types=1);

namespace App\Core\Actions\Contracts;

use App\Core\Actions\DTOs\ActionConfigDTO;
use App\Core\Actions\Enums\ActionType;

/**
 * Interface que cada modulo deve implementar para definir suas acoes.
 * Segue o principio DIP (Dependency Inversion) - modulos dependem da abstracao.
 */
interface ModuleActionsInterface
{
    /**
     * Retorna o nome identificador do modulo.
     *
     * @return string
     */
    public function getModuleName(): string;

    /**
     * Retorna os tipos de acoes disponiveis para este modulo.
     *
     * @return array<ActionType>
     */
    public function getAvailableActions(): array;

    /**
     * Retorna a configuracao padrao de cada acao.
     *
     * @return array<string, ActionConfigDTO>
     */
    public function getDefaultConfig(): array;

    /**
     * Retorna o prefixo de permissao do modulo.
     * Ex: 'rat' resulta em permissoes como 'rat.view', 'rat.edit'
     *
     * @return string
     */
    public function getPermissionPrefix(): string;

    /**
     * Retorna acoes que requerem confirmacao antes de executar.
     *
     * @return array<ActionType>
     */
    public function getActionsRequiringConfirmation(): array;

    /**
     * Permite sobrescrever configuracoes baseado em condicoes.
     * Ex: desabilitar delete se registro estiver em uso.
     *
     * @param mixed $entity Entidade do dominio (opcional)
     * @return array<string, array> Overrides por acao
     */
    public function getConditionalOverrides(mixed $entity = null): array;

    /**
     * Verifica se uma acao pode ser executada em uma entidade.
     * Usado pelo ActionPolicy para validacao server-side.
     *
     * @param ActionType $action Tipo da acao
     * @param mixed $entity Entidade do dominio
     * @return bool
     */
    public function canExecute(ActionType $action, mixed $entity): bool;
}
