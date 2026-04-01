<?php

declare(strict_types=1);

namespace App\Modules\Rat\DTOs;

/**
 * DTO de filtro para listagem/paginação de ocorrências RAT.
 *
 * Encapsula os critérios de busca entre a camada HTTP e
 * RatOcorrenciaService::paginate(), garantindo tipagem estrita.
 */
readonly class RatOcorrenciaFiltroDTO
{
    public function __construct(
        public ?int    $status    = null,
        public ?string $numeroBos = null,
        public int     $porPagina = 15,
    ) {}

    /**
     * Cria o DTO a partir de um array (p.ex.: request()->only([...])).
     */
    public static function fromArray(array $data, int $porPagina = 15): self
    {
        return new self(
            status:    isset($data['status']) ? (int) $data['status'] : null,
            numeroBos: $data['numero_bos'] ?? null,
            porPagina: (int) ($data['per_page'] ?? $porPagina),
        );
    }
}
