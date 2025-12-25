<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Application\UseCases;

use App\Models\User;
use App\Modules\Demandas\Domain\Entities\Task;
use App\Modules\Demandas\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\Demandas\Domain\ValueObjects\Impacto;
use App\Modules\Demandas\Domain\ValueObjects\TaskStatus;
use App\Modules\Demandas\Domain\ValueObjects\TipoTask;
use App\Modules\Demandas\Domain\ValueObjects\Urgencia;

/**
 * Use Case: Criar Nova Task (Portal Self-Service)
 *
 * Permite que qualquer usuário autenticado abra um chamado para TI
 */
class CreateTaskUseCase
{
    public function __construct(
        private readonly TaskRepositoryInterface $repository
    ) {
    }

    /**
     * Executar criação de task
     *
     * @param array{
     *     tipo: string,
     *     titulo: string,
     *     descricao?: string,
     *     categoria?: string,
     *     subcategoria?: string,
     *     urgencia?: string,
     *     impacto?: string,
     *     campos_customizados?: array
     * } $data
     * @param User $solicitante Usuário que está abrindo o chamado
     */
    public function execute(array $data, User $solicitante): Task
    {
        // Validar tipo
        $tipo = TipoTask::from($data['tipo']);

        // Para usuários comuns, permitir apenas Incidentes e Solicitações
        // Mudanças e Problemas são apenas para agentes TI
        if (! $solicitante->can('demandas.manage')) {
            if (in_array($tipo, [TipoTask::MUDANCA, TipoTask::PROBLEMA], true)) {
                throw new \InvalidArgumentException(
                    'Usuários não podem criar Mudanças ou Problemas. Apenas Incidentes e Solicitações.'
                );
            }
        }

        // Se usuário não especificou urgência/impacto, usar padrões
        $urgencia = isset($data['urgencia'])
            ? Urgencia::from($data['urgencia'])
            : Urgencia::MEDIA;

        $impacto = isset($data['impacto'])
            ? Impacto::from($data['impacto'])
            : Impacto::BAIXO;

        // Preparar dados para criação
        $taskData = [
            'tipo' => $tipo->value,
            'titulo' => $data['titulo'],
            'descricao' => $data['descricao'] ?? null,
            'status' => TaskStatus::ABERTA->value,
            'urgencia' => $urgencia->value,
            'impacto' => $impacto->value,
            'solicitante_id' => $solicitante->id,
            'categoria' => $data['categoria'] ?? null,
            'subcategoria' => $data['subcategoria'] ?? null,
            'campos_customizados' => $data['campos_customizados'] ?? null,
        ];

        // Roteamento automático básico (pode ser expandido com regras mais complexas)
        $taskData['atribuido_para_id'] = $this->determinarResponsavel($data['categoria'] ?? null);

        // Criar task
        $task = $this->repository->create($taskData);

        // TODO: Disparar eventos
        // - Notificar responsável
        // - Criar instância de SLA
        // - Registrar no audit log

        return $task;
    }

    /**
     * Determina responsável automático baseado na categoria
     * (Roteamento Inteligente conforme papiro)
     */
    private function determinarResponsavel(?string $categoria): ?int
    {
        // TODO: Implementar lógica de roteamento inteligente
        // - Buscar grupo responsável pela categoria
        // - Buscar usuário com menor carga de trabalho no grupo
        // - Round-robin ou load balancing

        // Por enquanto, retorna null (será atribuído manualmente)
        return null;
    }
}
