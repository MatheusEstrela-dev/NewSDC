<?php

declare(strict_types=1);

namespace App\Modules\Compdec\DTOs;

class AnexoDTO
{
    public function __construct(
        public string $tipo,
        public string $titulo,
        public ?string $descricao = null,
        public ?string $numero = null,
        public ?string $dataEmissao = null,
        public ?string $dataValidade = null,
        public ?string $legacyArquivo = null,
        public ?int $legacyId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            tipo: (string) ($data['tipo'] ?? 'outros'),
            titulo: (string) ($data['titulo'] ?? ''),
            descricao: $data['descricao'] ?? null,
            numero: $data['numero'] ?? null,
            dataEmissao: $data['data_emissao'] ?? null,
            dataValidade: $data['data_validade'] ?? null,
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
            'tipo' => $this->tipo,
            'titulo' => $this->titulo,
            'descricao' => $this->descricao,
            'numero' => $this->numero,
            'data_emissao' => $this->dataEmissao,
            'data_validade' => $this->dataValidade,
            'legacy_arquivo' => $this->legacyArquivo,
            'legacy_id' => $this->legacyId,
        ];
    }
}
