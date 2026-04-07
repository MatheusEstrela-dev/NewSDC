<?php

namespace App\Modules\Compdec\DTOs;

class OrgaoDTO
{
    public function __construct(
        public string $codigo,
        public string $nome,
        public string $tipo, // 'cedec' | 'redec' | 'compdec'
        public ?int $municipioId = null,
        public ?int $orgaoSuperiorId = null,
        public string $status = 'ativo',
        public ?string $email = null,
        public ?string $telefone = null,
        public ?string $endereco = null,
        public ?string $responsavelNome = null,
        public ?string $responsavelCpf = null,
        public ?string $responsavelTelefone = null,
        public ?string $responsavelEmail = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?array $abrangencia = null,
        public ?array $metadata = null,
    ) {}

    /**
     * Converter para array para persistência
     */
    public function toArray(): array
    {
        return [
            'codigo' => $this->codigo,
            'nome' => $this->nome,
            'tipo' => $this->tipo,
            'municipio_id' => $this->municipioId,
            'orgao_superior_id' => $this->orgaoSuperiorId,
            'status' => $this->status,
            'email' => $this->email,
            'telefone' => $this->telefone,
            'endereco' => $this->endereco,
            'responsavel_nome' => $this->responsavelNome,
            'responsavel_cpf' => $this->responsavelCpf,
            'responsavel_telefone' => $this->responsavelTelefone,
            'responsavel_email' => $this->responsavelEmail,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'abrangencia' => $this->abrangencia,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Factory method a partir de request
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            codigo: $data['codigo'] ?? '',
            nome: $data['nome'] ?? '',
            tipo: $data['tipo'] ?? 'compdec',
            municipioId: isset($data['municipio_id']) ? (int)$data['municipio_id'] : null,
            orgaoSuperiorId: isset($data['orgao_superior_id']) ? (int)$data['orgao_superior_id'] : null,
            status: $data['status'] ?? 'ativo',
            email: $data['email'] ?? null,
            telefone: $data['telefone'] ?? null,
            endereco: $data['endereco'] ?? null,
            responsavelNome: $data['responsavel_nome'] ?? null,
            responsavelCpf: $data['responsavel_cpf'] ?? null,
            responsavelTelefone: $data['responsavel_telefone'] ?? null,
            responsavelEmail: $data['responsavel_email'] ?? null,
            latitude: isset($data['latitude']) ? (float)$data['latitude'] : null,
            longitude: isset($data['longitude']) ? (float)$data['longitude'] : null,
            abrangencia: $data['abrangencia'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }
}
