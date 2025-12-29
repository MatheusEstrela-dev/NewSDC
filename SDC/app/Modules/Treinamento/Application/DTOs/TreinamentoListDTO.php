<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Application\DTOs;

use App\Modules\Treinamento\Domain\Entities\Treinamento;
use Carbon\Carbon;

readonly class TreinamentoListDTO
{
    public function __construct(
        public int $id,
        public string $titulo,
        public ?string $descricao,
        public string $tipo,
        public string $tipoLabel,
        public string $tipoColor,
        public string $status,
        public string $statusLabel,
        public string $statusColor,
        public int $cargaHoraria,
        public ?string $instrutor,
        public ?string $local,
        public ?string $dataInicio,
        public ?string $dataFim,
        public ?int $numeroVagas,
        public ?int $vagasDisponiveis,
        public int $totalInscricoes,
        public int $totalModulos,
        public string $criadoEm,
    ) {
    }

    public static function fromEntity(Treinamento $treinamento): self
    {
        return new self(
            id: $treinamento->id,
            titulo: $treinamento->titulo,
            descricao: $treinamento->descricao,
            tipo: $treinamento->tipo->value,
            tipoLabel: $treinamento->tipo->getLabel(),
            tipoColor: $treinamento->tipo->getBadgeColor(),
            status: $treinamento->status->value,
            statusLabel: $treinamento->status->getLabel(),
            statusColor: $treinamento->status->getBadgeColor(),
            cargaHoraria: $treinamento->carga_horaria,
            instrutor: $treinamento->instrutor,
            local: $treinamento->local,
            dataInicio: $treinamento->data_inicio?->format('Y-m-d'),
            dataFim: $treinamento->data_fim?->format('Y-m-d'),
            numeroVagas: $treinamento->numero_vagas,
            vagasDisponiveis: $treinamento->vagas_disponiveis,
            totalInscricoes: $treinamento->total_inscricoes,
            totalModulos: $treinamento->modulos->count(),
            criadoEm: $treinamento->created_at->format('Y-m-d H:i:s'),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'titulo' => $this->titulo,
            'descricao' => $this->descricao,
            'tipo' => $this->tipo,
            'tipoLabel' => $this->tipoLabel,
            'tipoColor' => $this->tipoColor,
            'status' => $this->status,
            'statusLabel' => $this->statusLabel,
            'statusColor' => $this->statusColor,
            'cargaHoraria' => $this->cargaHoraria,
            'instrutor' => $this->instrutor,
            'local' => $this->local,
            'dataInicio' => $this->dataInicio,
            'dataFim' => $this->dataFim,
            'numeroVagas' => $this->numeroVagas,
            'vagasDisponiveis' => $this->vagasDisponiveis,
            'totalInscricoes' => $this->totalInscricoes,
            'totalModulos' => $this->totalModulos,
            'criadoEm' => $this->criadoEm,
        ];
    }

    public static function collection(iterable $treinamentos): array
    {
        return array_map(
            fn(Treinamento $t) => self::fromEntity($t)->toArray(),
            is_array($treinamentos) ? $treinamentos : iterator_to_array($treinamentos)
        );
    }
}
