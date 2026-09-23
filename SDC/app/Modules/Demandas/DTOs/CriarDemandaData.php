<?php

declare(strict_types=1);

namespace App\Modules\Demandas\DTOs;

use App\Modules\Demandas\Enums\Impacto;
use App\Modules\Demandas\Enums\Urgencia;
use App\Modules\Demandas\Enums\TipoDemanda;
use App\Modules\Demandas\Requests\StoreDemandaRequest;

final readonly class CriarDemandaData
{
    public function __construct(
        public TipoDemanda $tipo,
        public string $titulo,
        public string $descricao,
        public ?string $categoria,
        public ?string $subcategoria,
        public ?int $assuntoId,
        public ?Urgencia $urgencia,
        public ?Impacto $impacto,
        public int $solicitanteId,
        public ?int $atribuidoParaId = null,
        public array $tags = [],
    ) {}

    public static function fromRequest(StoreDemandaRequest $request): self
    {
        $data = $request->validated();
        return new self(
            tipo: TipoDemanda::from($data['tipo']),
            titulo: $data['titulo'],
            descricao: $data['descricao'],
            categoria: $data['categoria'] ?? null,
            subcategoria: $data['subcategoria'] ?? null,
            assuntoId: isset($data['assunto_id']) ? (int) $data['assunto_id'] : null,
            urgencia: isset($data['urgencia']) ? Urgencia::from($data['urgencia']) : null,
            impacto: isset($data['impacto']) ? Impacto::from($data['impacto']) : null,
            solicitanteId: (int) $request->user()->id,
            atribuidoParaId: isset($data['responsavel_id']) ? (int) $data['responsavel_id'] : null,
            tags: [],
        );
    }

    public function toArray(): array
    {
        return [
            'tipo' => $this->tipo,
            'titulo' => $this->titulo,
            'descricao' => $this->descricao,
            'categoria' => $this->categoria,
            'subcategoria' => $this->subcategoria,
            'assunto_id' => $this->assuntoId,
            'urgencia' => $this->urgencia,
            'impacto' => $this->impacto,
            'solicitante_id' => $this->solicitanteId,
            'atribuido_para_id' => $this->atribuidoParaId,
        ];
    }
}
