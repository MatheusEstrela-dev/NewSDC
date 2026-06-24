<?php

declare(strict_types=1);

namespace App\Modules\Pmda\DTOs;

readonly class PmdaPlanoDTO
{
    public function __construct(
        public int $municipioId,
        public int $userId,
        public ?string $acoes = null,
        public ?int $qtdCaminhao = null,
        public ?int $popAtMunicipio = null,
        public ?bool $cobraIss = null,
        public ?string $numLeiIss = null,
        public ?float $aliquotaIss = null,
        public ?string $respCobIss = null,
    ) {}

    public static function fromArray(array $data, int $userId, int $municipioId): self
    {
        return new self(
            municipioId: $municipioId,
            userId: $userId,
            acoes: $data['acoes'] ?? null,
            qtdCaminhao: isset($data['qtd_caminhao']) ? (int) $data['qtd_caminhao'] : null,
            popAtMunicipio: isset($data['pop_at_municipio']) ? (int) $data['pop_at_municipio'] : null,
            cobraIss: isset($data['cobra_iss']) ? (bool) $data['cobra_iss'] : null,
            numLeiIss: $data['num_lei_iss'] ?? null,
            aliquotaIss: isset($data['aliquota_iss']) ? (float) $data['aliquota_iss'] : null,
            respCobIss: $data['resp_cob_iss'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'municipio_id'     => $this->municipioId,
            'created_by'       => $this->userId,
            'acoes'            => $this->acoes,
            'qtd_caminhao'     => $this->qtdCaminhao,
            'pop_at_municipio' => $this->popAtMunicipio,
            'cobra_iss'        => $this->cobraIss,
            'num_lei_iss'      => $this->numLeiIss,
            'aliquota_iss'     => $this->aliquotaIss,
            'resp_cob_iss'     => $this->respCobIss,
        ], static fn ($v) => $v !== null);
    }
}
