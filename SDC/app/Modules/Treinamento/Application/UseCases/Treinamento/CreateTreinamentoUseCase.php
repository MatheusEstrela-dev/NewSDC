<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Application\UseCases\Treinamento;

use App\Models\User;
use App\Modules\Treinamento\Domain\Entities\Treinamento;
use App\Modules\Treinamento\Domain\Repositories\TreinamentoRepositoryInterface;
use App\Modules\Treinamento\Domain\ValueObjects\StatusTreinamento;

class CreateTreinamentoUseCase
{
    public function __construct(
        private readonly TreinamentoRepositoryInterface $repository
    ) {
    }

    public function execute(array $data, User $createdBy): Treinamento
    {
        $treinamentoData = [
            'titulo' => $data['titulo'],
            'descricao' => $data['descricao'] ?? null,
            'carga_horaria' => $data['carga_horaria'],
            'tipo' => $data['tipo'],
            'status' => StatusTreinamento::PLANEJADO->value,
            'instrutor' => $data['instrutor'] ?? null,
            'local' => $data['local'] ?? null,
            'data_inicio' => $data['data_inicio'] ?? null,
            'data_fim' => $data['data_fim'] ?? null,
            'numero_vagas' => $data['numero_vagas'] ?? null,
            'percentual_frequencia_minimo' => $data['percentual_frequencia_minimo'] ?? 75.00,
            'created_by' => $createdBy->id,
        ];

        return $this->repository->create($treinamentoData);
    }
}
