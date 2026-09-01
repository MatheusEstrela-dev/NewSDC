<?php

declare(strict_types=1);

namespace App\Modules\Sismos\DTOs;

use Carbon\CarbonImmutable;

final readonly class SismoDTO
{
    public function __construct(
        public string $fonte,
        public string $evento_id,
        public CarbonImmutable $origem_utc,
        public float $latitude,
        public float $longitude,
        public ?float $profundidade_km = null,
        public ?float $magnitude = null,
        public ?string $escala_magnitude = null,
        public ?string $modo = null,
        public ?string $regiao = null,
        public ?string $tipo_evento = null,
        public ?string $autor = null,
    ) {
    }

    /**
     * Filtro geografico. Necessario porque o catalogo do UnB devolve eventos do
     * mundo inteiro e traz "Brazil" generico no campo Local — filtrar por texto
     * nao funciona, tem de ser pelas coordenadas.
     *
     * @param array{min_lat: float, max_lat: float, min_lon: float, max_lon: float} $bbox
     */
    public function dentroDaBbox(array $bbox): bool
    {
        return $this->latitude >= $bbox['min_lat']
            && $this->latitude <= $bbox['max_lat']
            && $this->longitude >= $bbox['min_lon']
            && $this->longitude <= $bbox['max_lon'];
    }
}
