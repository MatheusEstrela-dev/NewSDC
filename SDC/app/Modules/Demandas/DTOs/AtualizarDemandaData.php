<?php

declare(strict_types=1);

namespace App\Modules\Demandas\DTOs;

use App\Modules\Demandas\Enums\Impacto;
use App\Modules\Demandas\Enums\Urgencia;
use App\Modules\Demandas\Enums\TipoDemanda;
use Illuminate\Http\Request;

final readonly class AtualizarDemandaData
{
    public function __construct(
        public ?TipoDemanda $tipo,
        public ?string $titulo,
        public ?string $descricao,
        public ?string $categoria,
        public ?string $subcategoria,
        public ?Urgencia $urgencia,
        public ?Impacto $impacto,
        public ?int $atribuidoParaId,
        public ?array $tags,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            tipo: $request->filled('tipo') ? TipoDemanda::from($request->string('tipo')->value()) : null,
            titulo: $request->filled('titulo') ? $request->string('titulo')->value() : null,
            descricao: $request->filled('descricao') ? $request->string('descricao')->value() : null,
            categoria: $request->input('categoria'),
            subcategoria: $request->input('subcategoria'),
            urgencia: $request->filled('urgencia') ? Urgencia::from($request->string('urgencia')->value()) : null,
            impacto: $request->filled('impacto') ? Impacto::from($request->string('impacto')->value()) : null,
            atribuidoParaId: $request->filled('responsavel_id') ? (int) $request->input('responsavel_id') : null,
            tags: $request->input('tags'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'tipo' => $this->tipo,
            'titulo' => $this->titulo,
            'descricao' => $this->descricao,
            'categoria' => $this->categoria,
            'subcategoria' => $this->subcategoria,
            'urgencia' => $this->urgencia,
            'impacto' => $this->impacto,
            'atribuido_para_id' => $this->atribuidoParaId,
        ], fn($value) => $value !== null);
    }
}
