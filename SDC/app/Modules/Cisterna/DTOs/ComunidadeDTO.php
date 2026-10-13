<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\DTOs;

use App\Modules\Shared\DTOs\Traits\SafeCastingTrait;

final readonly class ComunidadeDTO
{
    use SafeCastingTrait;

    public function __construct(
        public int $municipioId,
        public string $nome,
        public ?bool $ativa = null,
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
            // Ausente ou null = nao mexer: o update nao reativa comunidade inativa
            // e, no create, vale o default true da coluna. false explicito grava.
            ativa: self::asBoolOrNull($d['ativa'] ?? null),
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
            ...($this->ativa !== null ? ['ativa' => $this->ativa] : []),
            // So quando preenchido (carga do legado): o update HTTP nao envia
            // legacy_id e o null zerava o vinculo do registro migrado.
            ...($this->legacyId !== null ? ['legacy_id' => $this->legacyId] : []),
        ];
    }
}
