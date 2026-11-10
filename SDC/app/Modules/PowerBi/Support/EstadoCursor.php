<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Support;

use Illuminate\Support\Carbon;

/** Posicao keyset (updated_at, id) da ultima linha materializada de uma tabela. */
final class EstadoCursor
{
    public function __construct(
        public readonly ?Carbon $atualizadoEm,
        public readonly ?string $id,
    ) {
    }
}
