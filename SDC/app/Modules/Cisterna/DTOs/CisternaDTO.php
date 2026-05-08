<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\DTOs;

class CisternaDTO
{
    public function __construct(
        public int $municipioId,
        public string $codigo,
        public string $nome,
        public string $tipo,
        public string $status = 'pendente',
        public ?string $endereco = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?int $capacidadeLitros = null,
        public ?string $dataInstalacao = null,
        public ?string $responsavelNome = null,
        public ?string $responsavelTelefone = null,
        public ?string $observacoes = null,
        public ?int $legacyId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            municipioId: (int) ($data['municipio_id'] ?? 0),
            codigo: (string) ($data['codigo'] ?? ''),
            nome: (string) ($data['nome'] ?? ''),
            tipo: (string) ($data['tipo'] ?? 'comunitaria'),
            status: (string) ($data['status'] ?? 'pendente'),
            endereco: $data['endereco'] ?? null,
            latitude: isset($data['latitude']) && $data['latitude'] !== '' ? (float) $data['latitude'] : null,
            longitude: isset($data['longitude']) && $data['longitude'] !== '' ? (float) $data['longitude'] : null,
            capacidadeLitros: isset($data['capacidade_litros']) && $data['capacidade_litros'] !== '' ? (int) $data['capacidade_litros'] : null,
            dataInstalacao: $data['data_instalacao'] ?? null,
            responsavelNome: $data['responsavel_nome'] ?? null,
            responsavelTelefone: $data['responsavel_telefone'] ?? null,
            observacoes: $data['observacoes'] ?? null,
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
            'codigo' => $this->codigo,
            'nome' => $this->nome,
            'tipo' => $this->tipo,
            'status' => $this->status,
            'endereco' => $this->endereco,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'capacidade_litros' => $this->capacidadeLitros,
            'data_instalacao' => $this->dataInstalacao,
            'responsavel_nome' => $this->responsavelNome,
            'responsavel_telefone' => $this->responsavelTelefone,
            'observacoes' => $this->observacoes,
            'legacy_id' => $this->legacyId,
        ];
    }
}
