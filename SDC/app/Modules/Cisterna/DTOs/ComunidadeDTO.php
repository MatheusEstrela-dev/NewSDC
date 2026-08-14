<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\DTOs;

final readonly class ComunidadeDTO
{
    public function __construct(
        public int $municipioId,
        public string $nome,
        public bool $ativa = true,
        public ?int $legacyId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $d
     */
    public static function deValidados(array $d): self
    {
        return new self(
            municipioId: (int) $d['municipio_id'],
            nome: trim((string) $d['nome']),
            ativa: (bool) ($d['ativa'] ?? true),
            legacyId: isset($d['legacy_id']) ? (int) $d['legacy_id'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'municipio_id' => $this->municipioId,
            'nome' => $this->nome,
            'ativa' => $this->ativa,
            'legacy_id' => $this->legacyId,
        ];
    }
}
