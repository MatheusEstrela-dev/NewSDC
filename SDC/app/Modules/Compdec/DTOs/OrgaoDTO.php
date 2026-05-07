<?php

declare(strict_types=1);

namespace App\Modules\Compdec\DTOs;

class OrgaoDTO
{
    /**
     * @param  array<string, mixed>|null  $abrangencia
     * @param  array<string, mixed>|null  $metadata
     * @param  array<string, mixed>|null  $capacidades
     */
    public function __construct(
        public string $codigo,
        public string $nome,
        public string $tipo,
        public ?int $municipioId = null,
        public ?int $orgaoSuperiorId = null,
        public string $status = 'ativo',
        // Contato
        public ?string $email = null,
        public ?string $emailSecundario = null,
        public ?string $emailTerciario = null,
        public ?string $telefone = null,
        public ?string $telefoneSecundario = null,
        public ?string $fax = null,
        public ?string $endereco = null,
        // Responsavel
        public ?string $responsavelNome = null,
        public ?string $responsavelCpf = null,
        public ?string $responsavelTelefone = null,
        public ?string $responsavelEmail = null,
        // Geo
        public ?float $latitude = null,
        public ?float $longitude = null,
        // Atos legais
        public ?string $leiCriacaoNumero = null,
        public ?string $leiCriacaoData = null,
        public ?string $decretoNumero = null,
        public ?string $decretoData = null,
        public ?string $portariaNumero = null,
        public ?string $portariaData = null,
        // Quantitativos
        public int $qtdEfetivo = 0,
        public int $qtdNupdec = 0,
        // Capacidades estruturais
        public bool $temSedePropria = false,
        public bool $temViatura = false,
        public bool $temMapeamentoRisco = false,
        public bool $temSimulado = false,
        public bool $temCartaoPdc = false,
        // JSONB
        public ?array $abrangencia = null,
        public ?array $metadata = null,
        public ?array $capacidades = null,
        // ETL
        public ?int $legacyId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            codigo: (string) ($data['codigo'] ?? ''),
            nome: (string) ($data['nome'] ?? ''),
            tipo: (string) ($data['tipo'] ?? 'compdec'),
            municipioId: isset($data['municipio_id']) && $data['municipio_id'] !== '' ? (int) $data['municipio_id'] : null,
            orgaoSuperiorId: isset($data['orgao_superior_id']) && $data['orgao_superior_id'] !== '' ? (int) $data['orgao_superior_id'] : null,
            status: (string) ($data['status'] ?? 'ativo'),
            email: $data['email'] ?? null,
            emailSecundario: $data['email_secundario'] ?? null,
            emailTerciario: $data['email_terciario'] ?? null,
            telefone: $data['telefone'] ?? null,
            telefoneSecundario: $data['telefone_secundario'] ?? null,
            fax: $data['fax'] ?? null,
            endereco: $data['endereco'] ?? null,
            responsavelNome: $data['responsavel_nome'] ?? null,
            responsavelCpf: $data['responsavel_cpf'] ?? null,
            responsavelTelefone: $data['responsavel_telefone'] ?? null,
            responsavelEmail: $data['responsavel_email'] ?? null,
            latitude: isset($data['latitude']) && $data['latitude'] !== '' ? (float) $data['latitude'] : null,
            longitude: isset($data['longitude']) && $data['longitude'] !== '' ? (float) $data['longitude'] : null,
            leiCriacaoNumero: $data['lei_criacao_numero'] ?? null,
            leiCriacaoData: $data['lei_criacao_data'] ?? null,
            decretoNumero: $data['decreto_numero'] ?? null,
            decretoData: $data['decreto_data'] ?? null,
            portariaNumero: $data['portaria_numero'] ?? null,
            portariaData: $data['portaria_data'] ?? null,
            qtdEfetivo: (int) ($data['qtd_efetivo'] ?? 0),
            qtdNupdec: (int) ($data['qtd_nupdec'] ?? 0),
            temSedePropria: (bool) ($data['tem_sede_propria'] ?? false),
            temViatura: (bool) ($data['tem_viatura'] ?? false),
            temMapeamentoRisco: (bool) ($data['tem_mapeamento_risco'] ?? false),
            temSimulado: (bool) ($data['tem_simulado'] ?? false),
            temCartaoPdc: (bool) ($data['tem_cartao_pdc'] ?? false),
            abrangencia: $data['abrangencia'] ?? null,
            metadata: $data['metadata'] ?? null,
            capacidades: $data['capacidades'] ?? null,
            legacyId: isset($data['legacy_id']) ? (int) $data['legacy_id'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $metadata = $this->metadata ?? [];
        if ($this->capacidades !== null) {
            $metadata['capacidades'] = $this->capacidades;
        }

        return [
            'codigo' => $this->codigo,
            'nome' => $this->nome,
            'tipo' => $this->tipo,
            'municipio_id' => $this->municipioId,
            'orgao_superior_id' => $this->orgaoSuperiorId,
            'status' => $this->status,
            'email' => $this->email,
            'email_secundario' => $this->emailSecundario,
            'email_terciario' => $this->emailTerciario,
            'telefone' => $this->telefone,
            'telefone_secundario' => $this->telefoneSecundario,
            'fax' => $this->fax,
            'endereco' => $this->endereco,
            'responsavel_nome' => $this->responsavelNome,
            'responsavel_cpf' => $this->responsavelCpf,
            'responsavel_telefone' => $this->responsavelTelefone,
            'responsavel_email' => $this->responsavelEmail,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'lei_criacao_numero' => $this->leiCriacaoNumero,
            'lei_criacao_data' => $this->leiCriacaoData,
            'decreto_numero' => $this->decretoNumero,
            'decreto_data' => $this->decretoData,
            'portaria_numero' => $this->portariaNumero,
            'portaria_data' => $this->portariaData,
            'qtd_efetivo' => $this->qtdEfetivo,
            'qtd_nupdec' => $this->qtdNupdec,
            'tem_sede_propria' => $this->temSedePropria,
            'tem_viatura' => $this->temViatura,
            'tem_mapeamento_risco' => $this->temMapeamentoRisco,
            'tem_simulado' => $this->temSimulado,
            'tem_cartao_pdc' => $this->temCartaoPdc,
            'abrangencia' => $this->abrangencia,
            'metadata' => $metadata,
            'legacy_id' => $this->legacyId,
        ];
    }
}
