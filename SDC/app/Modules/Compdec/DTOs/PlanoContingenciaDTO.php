<?php

declare(strict_types=1);

namespace App\Modules\Compdec\DTOs;

class PlanoContingenciaDTO
{
    public function __construct(
        public string $versao,
        public ?string $observacoes = null,
        public bool $ativo = false,
        public ?int $tamanhoBytes = null,
        public ?string $legacyArquivo = null,
        public ?int $legacyId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            versao: (string) ($data['versao'] ?? ''),
            observacoes: $data['observacoes'] ?? null,
            ativo: (bool) ($data['ativo'] ?? false),
            tamanhoBytes: isset($data['tamanho_bytes']) ? (int) $data['tamanho_bytes'] : null,
            legacyArquivo: $data['legacy_arquivo'] ?? null,
            legacyId: isset($data['legacy_id']) ? (int) $data['legacy_id'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'versao' => $this->versao,
            'observacoes' => $this->observacoes,
            'ativo' => $this->ativo,
            'tamanho_bytes' => $this->tamanhoBytes,
            'legacy_arquivo' => $this->legacyArquivo,
            'legacy_id' => $this->legacyId,
        ];
    }
}
