<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Support;

use App\Modules\Treinamento\DTOs\EstadoTrilha;
use App\Modules\Treinamento\Models\Treinamento;

/**
 * Props da pagina da sala de aula, as mesmas nos dois escopos.
 */
final class SalaDeAulaProps
{
    /**
     * @return array<string, mixed>
     */
    public static function de(Treinamento $curso, EstadoTrilha $estado, string $escopo): array
    {
        return [
            'curso' => [
                'id' => $curso->id,
                'slug' => $curso->link_publico_slug,
                'titulo' => $curso->titulo,
                'carga_horaria' => $curso->carga_horaria,
            ],
            'estado' => $estado->toArray(),
            'escopo' => $escopo,
        ];
    }
}
