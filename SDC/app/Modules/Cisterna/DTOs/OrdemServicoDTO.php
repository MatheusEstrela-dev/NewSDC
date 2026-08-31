<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\DTOs;

final readonly class OrdemServicoDTO
{
    public function __construct(
        public int $loteId,
        public string $nome,
        public ?string $observacao = null,
        // Legado: coluna link_doc, que guarda URL do SEI e nao arquivo. A
        // collection documento_os do MediaLibrary continua existindo, para o
        // documento anexado de verdade -- sao coisas diferentes.
        public ?string $documentoUrl = null,
        public ?int $legacyId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $d
     */
    public static function deValidados(array $d): self
    {
        return new self(
            loteId: (int) $d['lote_id'],
            nome: trim((string) $d['nome']),
            observacao: $d['observacao'] ?? null,
            documentoUrl: $d['documento_url'] ?? null,
            legacyId: isset($d['legacy_id']) ? (int) $d['legacy_id'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'lote_id' => $this->loteId,
            'nome' => $this->nome,
            'observacao' => $this->observacao,
            'documento_url' => $this->documentoUrl,
            'legacy_id' => $this->legacyId,
        ];
    }
}
