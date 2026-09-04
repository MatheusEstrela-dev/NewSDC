<?php

declare(strict_types=1);

namespace App\Modules\Shared\Geo;

use InvalidArgumentException;

/**
 * Par latitude/longitude validado, em WGS 84 (SRID 4326).
 *
 * Existe para que coordenada invalida morra na construcao, e nao vire ponto
 * errado no mapa. Antes disso, um par trocado ou fora de faixa atravessava o
 * sistema inteiro e so aparecia como marcador no lugar errado -- que num mapa
 * de Defesa Civil tem consequencia operacional.
 *
 * NAO valida se a coordenada faz sentido para o dominio. "Esta em Minas?" e
 * "e o Golfo da Guine?" sao perguntas de dominio, e vivem nos DTOs de cada
 * fonte: um par (0,0) e geograficamente valido, so nao e plausivel para uma
 * estacao do INMET.
 */
final readonly class Coordenada
{
    public function __construct(
        public float $latitude,
        public float $longitude,
    ) {
        if ($latitude < -90.0 || $latitude > 90.0) {
            throw new InvalidArgumentException("Latitude fora da faixa -90..90: {$latitude}");
        }

        if ($longitude < -180.0 || $longitude > 180.0) {
            throw new InvalidArgumentException("Longitude fora da faixa -180..180: {$longitude}");
        }
    }

    /**
     * A ORDEM aqui e a pegadinha que este objeto existe para eliminar.
     *
     * KML, GeoJSON e ST_MakePoint usam (longitude, latitude). Leaflet e a
     * notacao que as pessoas escrevem usam (latitude, longitude). Trocar os
     * dois nao levanta erro nenhum: produz um ponto plausivel no lugar errado.
     */
    public static function deLonLat(float $longitude, float $latitude): self
    {
        return new self($latitude, $longitude);
    }

    /** Ordem do PostGIS e do GeoJSON: X antes de Y. */
    public function paraLonLat(): array
    {
        return [$this->longitude, $this->latitude];
    }

    /** Ordem do Leaflet. */
    public function paraLatLon(): array
    {
        return [$this->latitude, $this->longitude];
    }
}
