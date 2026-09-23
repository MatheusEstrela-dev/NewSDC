<?php

declare(strict_types=1);

namespace App\Modules\Demandas\DTOs;

use App\Modules\Demandas\Enums\StatusDemanda;
use App\Modules\Demandas\Enums\TipoDemanda;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class FiltroDemanda
{
    public function __construct(
        public readonly ?string $search,
        public readonly ?string $status,
        public readonly ?string $tipo,
        public readonly ?int $responsavelId,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:200'],
            'status' => ['nullable', Rule::enum(StatusDemanda::class)],
            'tipo' => ['nullable', Rule::enum(TipoDemanda::class)],
            'responsavel_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        return new self(
            search: $data['search'] ?? null,
            status: $data['status'] ?? null,
            tipo: $data['tipo'] ?? null,
            responsavelId: isset($data['responsavel_id']) ? (int) $data['responsavel_id'] : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'search' => $this->search,
            'status' => $this->status,
            'tipo' => $this->tipo,
            'responsavel_id' => $this->responsavelId,
        ], static fn ($value) => $value !== null && $value !== '');
    }
}
