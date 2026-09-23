<?php

declare(strict_types=1);

namespace App\Modules\Demandas\DTOs;

use App\Modules\Demandas\Enums\Impacto;
use App\Modules\Demandas\Enums\Urgencia;
use App\Modules\Demandas\Enums\TipoDemanda;
use App\Modules\Demandas\Requests\UpdateDemandaRequest;

final readonly class AtualizarDemandaData
{
    public function __construct(
        public ?TipoDemanda $tipo,
        public ?string $titulo,
        public ?string $descricao,
        public ?string $categoria,
        public ?string $subcategoria,
        public ?int $assuntoId,
        public ?Urgencia $urgencia,
        public ?Impacto $impacto,
        public array $presentes,
    ) {}

    public static function fromRequest(UpdateDemandaRequest $request): self
    {
        $data = $request->validated();
        return new self(
            tipo: isset($data['tipo']) ? TipoDemanda::from($data['tipo']) : null,
            titulo: $data['titulo'] ?? null,
            descricao: $data['descricao'] ?? null,
            categoria: $data['categoria'] ?? null,
            subcategoria: $data['subcategoria'] ?? null,
            assuntoId: isset($data['assunto_id']) ? (int) $data['assunto_id'] : null,
            urgencia: isset($data['urgencia']) ? Urgencia::from($data['urgencia']) : null,
            impacto: isset($data['impacto']) ? Impacto::from($data['impacto']) : null,
            presentes: array_keys($data),
        );
    }

    public function toArray(): array
    {
        return array_intersect_key([
            'tipo' => $this->tipo,
            'titulo' => $this->titulo,
            'descricao' => $this->descricao,
            'categoria' => $this->categoria,
            'subcategoria' => $this->subcategoria,
            'assunto_id' => $this->assuntoId,
            'urgencia' => $this->urgencia,
            'impacto' => $this->impacto,
        ], array_flip($this->presentes));
    }
}
