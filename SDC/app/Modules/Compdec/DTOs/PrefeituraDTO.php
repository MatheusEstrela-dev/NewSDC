<?php

declare(strict_types=1);

namespace App\Modules\Compdec\DTOs;

class PrefeituraDTO
{
    public function __construct(
        public int $municipioId,
        public ?string $prefeitoNome = null,
        public ?string $prefeitoTelefone = null,
        public ?string $prefeitoCelular = null,
        public ?string $prefeitoEmail = null,
        public ?string $endereco = null,
        public ?string $bairro = null,
        public ?string $cep = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public bool $inssTemCobranca = false,
        public ?float $inssAliquota = null,
        public ?string $inssLeiCobranca = null,
        public ?string $inssResponsavel = null,
        public ?int $legacyId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(int $municipioId, array $data): self
    {
        return new self(
            municipioId: $municipioId,
            prefeitoNome: $data['prefeito_nome'] ?? null,
            prefeitoTelefone: $data['prefeito_telefone'] ?? null,
            prefeitoCelular: $data['prefeito_celular'] ?? null,
            prefeitoEmail: $data['prefeito_email'] ?? null,
            endereco: $data['endereco'] ?? null,
            bairro: $data['bairro'] ?? null,
            cep: $data['cep'] ?? null,
            latitude: isset($data['latitude']) && $data['latitude'] !== '' ? (float) $data['latitude'] : null,
            longitude: isset($data['longitude']) && $data['longitude'] !== '' ? (float) $data['longitude'] : null,
            inssTemCobranca: (bool) ($data['inss_tem_cobranca'] ?? false),
            inssAliquota: isset($data['inss_aliquota']) && $data['inss_aliquota'] !== '' ? (float) $data['inss_aliquota'] : null,
            inssLeiCobranca: $data['inss_lei_cobranca'] ?? null,
            inssResponsavel: $data['inss_responsavel'] ?? null,
            legacyId: isset($data['legacy_id']) ? (int) $data['legacy_id'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'municipio_id' => $this->municipioId,
            'prefeito_nome' => $this->prefeitoNome,
            'prefeito_telefone' => $this->prefeitoTelefone,
            'prefeito_celular' => $this->prefeitoCelular,
            'prefeito_email' => $this->prefeitoEmail,
            'endereco' => $this->endereco,
            'bairro' => $this->bairro,
            'cep' => $this->cep,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'inss_tem_cobranca' => $this->inssTemCobranca,
            'inss_aliquota' => $this->inssAliquota,
            'inss_lei_cobranca' => $this->inssLeiCobranca,
            'inss_responsavel' => $this->inssResponsavel,
            'legacy_id' => $this->legacyId,
        ];
    }
}
