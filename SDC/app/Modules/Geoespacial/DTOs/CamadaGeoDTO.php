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
        // Procedencia e moderacao. Default 'estadual'/'aprovada' para que a
        // ingestao existente -- e qualquer envio da CEDEC -- siga publicando
        // direto, sem precisar informar nada.
        public string $origem = 'estadual',
        public ?int $municipioId = null,
        public ?int $orgaoId = null,
        public ?int $enviadoPor = null,
        public string $status = 'aprovada',
        public ?string $arquivoCaminho = null,
    ) {
    }
}
