<?php

declare(strict_types=1);

namespace App\Modules\Demandas\DTOs;

use App\Modules\Demandas\Enums\EtapaDemanda;
use App\Modules\Demandas\Enums\PrioridadeSimples;
use App\Modules\Demandas\Enums\StatusDemanda;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final readonly class FiltroDemanda
{
    public function __construct(
        public ?string $search = null,
        public ?string $etapa = null,
        public ?string $status = null,
        public ?string $prioridade = null,
        public ?int $assuntoId = null,
        public ?int $solicitanteId = null,
        public ?int $responsavelId = null,
        public ?int $criadoPorId = null,
        public ?string $dataInicial = null,
        public ?string $dataFinal = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:200'],
            'etapa' => ['nullable', Rule::enum(EtapaDemanda::class)],
            'status' => ['nullable', Rule::enum(StatusDemanda::class)],
            'prioridade' => ['nullable', Rule::enum(PrioridadeSimples::class)],
            'assunto_id' => ['nullable', 'integer'],
            'solicitante_id' => ['nullable', 'integer'],
            'responsavel_id' => ['nullable', 'integer'],
            'criado_por_id' => ['nullable', 'integer'],
            'data_inicial' => ['nullable', 'date'],
            'data_final' => ['nullable', 'date', 'after_or_equal:data_inicial'],
        ]);
        $int = static fn (string $k): ?int => isset($data[$k]) ? (int) $data[$k] : null;

        return new self(
            search: $data['search'] ?? null,
            etapa: $data['etapa'] ?? null,
            status: $data['status'] ?? null,
            prioridade: $data['prioridade'] ?? null,
            assuntoId: $int('assunto_id'),
            solicitanteId: $int('solicitante_id'),
            responsavelId: $int('responsavel_id'),
            criadoPorId: $int('criado_por_id'),
            dataInicial: $data['data_inicial'] ?? null,
            dataFinal: $data['data_final'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'search' => $this->search,
            'etapa' => $this->etapa,
            'status' => $this->status,
            'prioridade' => $this->prioridade,
            'assunto_id' => $this->assuntoId,
            'solicitante_id' => $this->solicitanteId,
            'responsavel_id' => $this->responsavelId,
            'criado_por_id' => $this->criadoPorId,
            'data_inicial' => $this->dataInicial,
            'data_final' => $this->dataFinal,
        ], static fn ($value) => $value !== null && $value !== '');
    }
}
