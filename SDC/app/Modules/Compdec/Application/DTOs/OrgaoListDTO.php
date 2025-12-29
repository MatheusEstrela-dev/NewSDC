<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Application\DTOs;

use App\Modules\Compdec\Domain\Entities\Orgao;

readonly class OrgaoListDTO
{
    public function __construct(
        public int $id,
        public string $codigo,
        public string $nome,
        public string $tipo,
        public string $tipoLabel,
        public string $tipoSigla,
        public string $tipoBadgeColor,
        public string $status,
        public string $statusLabel,
        public string $statusBadgeColor,
        public ?string $municipioNome,
        public ?string $municipioUf,
        public ?int $municipioId,
        public ?string $orgaoSuperiorNome,
        public ?int $orgaoSuperiorId,
        public ?string $responsavelNome,
        public ?string $email,
        public ?string $telefone,
        public int $totalUsuarios,
        public int $totalSubordinados,
        public string $criadoEm,
        public string $atualizadoEm,
    ) {
    }

    public static function fromEntity(Orgao $orgao): self
    {
        return new self(
            id: $orgao->id,
            codigo: $orgao->codigo,
            nome: $orgao->nome,
            tipo: $orgao->tipo->value,
            tipoLabel: $orgao->tipo->getLabel(),
            tipoSigla: $orgao->tipo->getSigla(),
            tipoBadgeColor: $orgao->tipo->getBadgeColor(),
            status: $orgao->status->value,
            statusLabel: $orgao->status->getLabel(),
            statusBadgeColor: $orgao->status->getBadgeColor(),
            municipioNome: $orgao->municipio?->nome,
            municipioUf: $orgao->municipio?->uf,
            municipioId: $orgao->municipio?->id,
            orgaoSuperiorNome: $orgao->orgaoSuperior?->nome,
            orgaoSuperiorId: $orgao->orgaoSuperior?->id,
            responsavelNome: $orgao->responsavel_nome,
            email: $orgao->email,
            telefone: $orgao->telefone,
            totalUsuarios: $orgao->usuarios_count ?? 0,
            totalSubordinados: $orgao->subordinados_count ?? 0,
            criadoEm: $orgao->created_at?->toIso8601String() ?? '',
            atualizadoEm: $orgao->updated_at?->toIso8601String() ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'nome' => $this->nome,
            'tipo' => $this->tipo,
            'tipoLabel' => $this->tipoLabel,
            'tipoSigla' => $this->tipoSigla,
            'tipoBadgeColor' => $this->tipoBadgeColor,
            'status' => $this->status,
            'statusLabel' => $this->statusLabel,
            'statusBadgeColor' => $this->statusBadgeColor,
            'municipio' => $this->municipioNome ? [
                'id' => $this->municipioId,
                'nome' => $this->municipioNome,
                'uf' => $this->municipioUf,
            ] : null,
            'orgao_superior' => $this->orgaoSuperiorNome ? [
                'id' => $this->orgaoSuperiorId,
                'nome' => $this->orgaoSuperiorNome,
            ] : null,
            'responsavel_nome' => $this->responsavelNome,
            'email' => $this->email,
            'telefone' => $this->telefone,
            'usuarios_count' => $this->totalUsuarios,
            'subordinados_count' => $this->totalSubordinados,
            'created_at' => $this->criadoEm,
            'updated_at' => $this->atualizadoEm,
        ];
    }
}
