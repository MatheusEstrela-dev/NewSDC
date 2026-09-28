<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Importacao;

final class ResolvedorUsuarioLegado
{
    public function __construct(private readonly MapaImportacao $mapa) {}

    public function resolver(?int $legadoId): ?int
    {
        return $legadoId === null ? null : $this->mapa->alvo('users', (string) $legadoId);
    }
}
