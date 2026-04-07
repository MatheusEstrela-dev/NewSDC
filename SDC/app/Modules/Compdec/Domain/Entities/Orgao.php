<?php

namespace App\Modules\Compdec\Domain\Entities;

/**
 * Domain Entity para Órgão (CEDEC, REDEC, COMPDEC)
 * Representa a entidade de negócio sem dependências do framework
 */
class Orgao
{
    public function __construct(
        public readonly ?int $id,
        public string $codigo,
        public string $nome,
        public string $tipo, // 'cedec' | 'redec' | 'compdec'
        public ?int $municipioId,
        public ?int $orgaoSuperiorId,
        public string $status, // 'ativo' | 'inativo' | 'em_implantacao' | 'suspenso'
        public ?string $email,
        public ?string $telefone,
        public ?string $endereco,
        public ?string $responsavelNome,
        public ?string $responsavelCpf,
        public ?string $responsavelTelefone,
        public ?string $responsavelEmail,
        public ?float $latitude,
        public ?float $longitude,
        public ?array $abrangencia, // Array de IDs de municípios
        public ?array $metadata,
        public ?\DateTime $criadoEm,
        public ?\DateTime $atualizadoEm,
        public ?\DateTime $deletadoEm = null,
    ) {}

    /**
     * Factory method para criar entidade a partir de array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            codigo: $data['codigo'] ?? '',
            nome: $data['nome'] ?? '',
            tipo: $data['tipo'] ?? 'compdec',
            municipioId: $data['municipio_id'] ?? null,
            orgaoSuperiorId: $data['orgao_superior_id'] ?? null,
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
            criadoEm: isset($data['created_at']) ? new \DateTime($data['created_at']) : null,
            atualizadoEm: isset($data['updated_at']) ? new \DateTime($data['updated_at']) : null,
            deletadoEm: isset($data['deleted_at']) ? new \DateTime($data['deleted_at']) : null,
        );
    }

    /**
     * Converter para array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
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
            'created_at' => $this->criadoEm?->format('Y-m-d H:i:s'),
            'updated_at' => $this->atualizadoEm?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletadoEm?->format('Y-m-d H:i:s'),
        ];
    }

    // Métodos de negócio
    public function isCedec(): bool
    {
        return $this->tipo === 'cedec';
    }

    public function isRedec(): bool
    {
        return $this->tipo === 'redec';
    }

    public function isCompdec(): bool
    {
        return $this->tipo === 'compdec';
    }

    public function isAtivo(): bool
    {
        return $this->status === 'ativo';
    }

    public function getNivelHierarquico(): int
    {
        return match ($this->tipo) {
            'cedec' => 0,
            'redec' => 1,
            'compdec' => 2,
            default => 3,
        };
    }
}
