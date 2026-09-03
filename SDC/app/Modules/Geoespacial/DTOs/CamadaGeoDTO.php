<?php

declare(strict_types=1);

namespace App\Modules\Geoespacial\DTOs;

final readonly class CamadaGeoDTO
{
    /** @param list<FeicaoKmlDTO> $feicoes */
    public function __construct(
        public string $dominio,
        public string $nome,
        public string $arquivoNome,
        public ?string $emitidoEm,
        public ?string $validoAte,
        public ?string $nivel,
        public string $hashArquivo,
        public array $feicoes,
    ) {
    }
}
