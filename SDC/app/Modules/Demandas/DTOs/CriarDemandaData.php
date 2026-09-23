<?php

declare(strict_types=1);

namespace App\Modules\Demandas\DTOs;

use App\Modules\Demandas\Enums\Impacto;
use App\Modules\Demandas\Enums\Urgencia;
use App\Modules\Demandas\Enums\TipoDemanda;
use Illuminate\Http\Request;

final readonly class CriarDemandaData
{
    public function __construct(
        public TipoDemanda $tipo,
        public string $titulo,
        public string $descricao,
        public ?string $categoria,
        public ?string $subcategoria,
        public ?Urgencia $urgencia,
        public ?Impacto $impacto,
        public int $solicitanteId,
        public ?int $atribuidoParaId = null,
        public array $tags = [],
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            tipo: TipoDemanda::from($request->string('tipo')->value()),
            titulo: $request->string('titulo')->value(),
            descricao: $request->string('descricao')->value(),
            categoria: $request->input('categoria'),
            subcategoria: $request->input('subcategoria'),
            urgencia: $request->filled('urgencia') ? Urgencia::from($request->string('urgencia')->value()) : null,
            impacto: $request->filled('impacto') ? Impacto::from($request->string('impacto')->value()) : null,
            solicitanteId: (int) $request->user()->id,
            atribuidoParaId: $request->filled('responsavel_id') ? (int) $request->input('responsavel_id') : null,
            tags: $request->input('tags', []),
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
            'urgencia' => $this->urgencia,
            'impacto' => $this->impacto,
            'solicitante_id' => $this->solicitanteId,
            'atribuido_para_id' => $this->atribuidoParaId,
        ];
    }
}
