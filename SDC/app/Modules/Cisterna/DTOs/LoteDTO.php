<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\DTOs;

final readonly class LoteDTO
{
    public function __construct(
        public string $nome,
        public ?string $data = null,
        public ?string $observacao = null,
        public ?int $legacyId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $d
     */
    public static function deValidados(array $d): self
    {
        return new self(
            nome: trim((string) $d['nome']),
            data: $d['data'] ?? null,
            observacao: $d['observacao'] ?? null,
            legacyId: isset($d['legacy_id']) ? (int) $d['legacy_id'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'nome' => $this->nome,
            'data' => $this->data,
            'observacao' => $this->observacao,
            // So quando preenchido (carga do legado): o update HTTP nao envia
            // legacy_id e o null zerava o vinculo do registro migrado.
            ...($this->legacyId !== null ? ['legacy_id' => $this->legacyId] : []),
        ];
    }
}
