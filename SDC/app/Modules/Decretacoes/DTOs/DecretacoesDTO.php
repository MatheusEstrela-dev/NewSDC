<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\DTOs;

use Illuminate\Http\Request;

/**
 * Consolidated DTOs for the Decretacoes module.
 *
 * This file contains all Data Transfer Objects used in the Decretacoes workflow:
 * - ProcessoDTO: Main process data from request
 * - CampoData: Field data with value and type
 * - ItemData: Item containing multiple fields
 * - DesastreData: Disaster data with items (has mutable entradaCategoriaDesastreId)
 * - CategoriaData: Category containing disasters
 * - MunicipioData: Municipality with protocol and categories
 * - DesastreSubmissionDTO: Top-level submission containing municipalities
 *
 * Note: Using readonly properties (PHP 8.1+) instead of readonly classes (PHP 8.2+)
 * for broader compatibility.
 */

/**
 * ProcessoDTO (from ProcessoDataDTO)
 * Main process data extracted from request.
 */
class ProcessoDTO
{
    public function __construct(
        public readonly array $allData,
        public readonly array $municipios = [],
        public readonly ?string $informacoesDecreto = null,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            allData: $request->all(),
            municipios: $request->input('municipios', []),
            informacoesDecreto: $request->input('informacoes_decreto'),
        );
    }
}

/**
 * CampoData (from CampoDTO)
 * Field data with value and type information.
 */
class CampoData
{
    public function __construct(
        public readonly int $id,
        public readonly string $titulo,
        public readonly mixed $valor,
        public readonly string $tipo,
        public readonly ?int $entradaDesastreId = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            titulo: $data['titulo'],
            valor: $data['valor'] ?? null,
            tipo: $data['tipo'],
            entradaDesastreId: isset($data['entrada_desastre_id']) ? (int) $data['entrada_desastre_id'] : null,
        );
    }
}

/**
 * ItemData (from ItemDTO)
 * Item containing multiple fields.
 */
class ItemData
{
    /**
     * @param CampoData[] $campos
     */
    public function __construct(
        public readonly int $id,
        public readonly array $campos,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            campos: array_map(fn(array $campo) => CampoData::fromArray($campo), $data['campos'] ?? []),
        );
    }
}

/**
 * DesastreData (from DesastreDTO)
 * Disaster data with items.
 * Note: entradaCategoriaDesastreId is mutable (not readonly).
 */
class DesastreData
{
    /**
     * @param ItemData[] $items
     */
    public function __construct(
        public readonly int $id,
        public readonly ?string $descricao,
        public readonly array $items,
        public ?int $entradaCategoriaDesastreId = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            descricao: $data['descricao'] ?? null,
            items: array_map(fn(array $item) => ItemData::fromArray($item), $data['items'] ?? []),
            entradaCategoriaDesastreId: isset($data['entrada_categoria_desastre_id']) ? (int) $data['entrada_categoria_desastre_id'] : null,
        );
    }
}

/**
 * CategoriaData (from CategoriaDTO)
 * Category containing disasters.
 */
class CategoriaData
{
    /**
     * @param DesastreData[] $desastres
     */
    public function __construct(
        public readonly int $id,
        public readonly array $desastres,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            desastres: array_map(fn(array $desastre) => DesastreData::fromArray($desastre), $data['desastres'] ?? []),
        );
    }
}

/**
 * MunicipioData (from MunicipioDesastreDTO)
 * Municipality with protocol and categories.
 */
class MunicipioData
{
    /**
     * @param CategoriaData[] $categorias
     */
    public function __construct(
        public readonly int $id,
        public readonly ?string $nProtocoloFide,
        public readonly array $categorias,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            nProtocoloFide: $data['n_protocolo_fide'] ?? null,
            categorias: array_map(fn(array $categoria) => CategoriaData::fromArray($categoria), $data['categorias'] ?? []),
        );
    }
}

/**
 * DesastreSubmissionDTO (from DisasterSubmissionDTO)
 * Top-level submission containing municipalities.
 */
class DesastreSubmissionDTO
{
    /**
     * @param MunicipioData[] $municipios
     */
    public function __construct(
        public readonly array $municipios,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            municipios: array_map(fn(array $municipio) => MunicipioData::fromArray($municipio), $data['municipios'] ?? []),
        );
    }
}
