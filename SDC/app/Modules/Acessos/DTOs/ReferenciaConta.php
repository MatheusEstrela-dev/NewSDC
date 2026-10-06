<?php

declare(strict_types=1);

namespace App\Modules\Acessos\DTOs;

/**
 * Alvo de uma escrita no diretorio. Montada a partir de uma ContaDiretorio
 * recem-lida (ContaDiretorio::referencia): o DN nunca vem do banco.
 */
final readonly class ReferenciaConta
{
    public function __construct(
        public string $objectGuid,
        public string $dn,
        public string $login,
        public ?string $nomeExibicao = null,
    ) {}
}
