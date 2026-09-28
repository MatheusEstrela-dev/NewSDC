<?php

declare(strict_types=1);

namespace App\Modules\Demandas\DTOs;

use App\Modules\Demandas\Enums\Impacto;
use App\Modules\Demandas\Enums\PrioridadeSimples;
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
        public int $criadoPorId,
        public ?int $atribuidoParaId = null,
        public array $camposCustomizados = [],
    ) {}

    public static function fromRequest(StoreDemandaRequest $request): self
    {
        $data = $request->validated();
        $simples = PrioridadeSimples::tryFrom($data['prioridade_simples'] ?? '') ?? PrioridadeSimples::MEDIA;
        $autorId = (int) $request->user()->id;

        return new self(
            tipo: TipoDemanda::tryFrom($data['tipo'] ?? '') ?? TipoDemanda::SOLICITACAO,
            titulo: $data['titulo'],
            descricao: $data['descricao'],
            categoria: $data['categoria'] ?? null,
            subcategoria: $data['subcategoria'] ?? null,
            assuntoId: isset($data['assunto_id']) ? (int) $data['assunto_id'] : null,
            urgencia: isset($data['urgencia']) ? Urgencia::from($data['urgencia']) : $simples->urgencia(),
            impacto: isset($data['impacto']) ? Impacto::from($data['impacto']) : $simples->impacto(),
            solicitanteId: isset($data['solicitante_id']) ? (int) $data['solicitante_id'] : $autorId,
            criadoPorId: $autorId,
            atribuidoParaId: isset($data['responsavel_id']) ? (int) $data['responsavel_id'] : null,
            camposCustomizados: $data['campos_customizados'] ?? [],
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
            'criado_por_id' => $this->criadoPorId,
            'atribuido_para_id' => $this->atribuidoParaId,
        ];
    }
}
