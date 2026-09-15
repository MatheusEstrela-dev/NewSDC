<?php

declare(strict_types=1);

namespace App\Modules\Geoespacial\DTOs;

final readonly class FeicaoKmlDTO
{
    /**
     * @param string $kmlGeometria Fragmento de geometria PURO (MultiGeometry,
     *                             Polygon, LineString ou Point). O
     *                             ST_GeomFromKML nao aceita a tag Placemark,
     *                             entao guardar o Placemark inteiro aqui faria
     *                             o insert falhar.
     */
    public function __construct(
        public ?string $nome,
        public string $kmlGeometria,
    ) {
    }
}
