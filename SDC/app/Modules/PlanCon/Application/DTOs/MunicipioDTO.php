<?php

namespace App\Modules\PlanCon\Application\DTOs;

class MunicipioDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $nome,
        public readonly ?string $codigoIbge,
        public readonly ?bool $temPlano,
        public readonly ?string $situacaoPlano,
        public readonly ?string $dataUltimaAtualizacao,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            nome: $data['nome'],
            codigoIbge: $data['codigo_ibge'] ?? null,
            temPlano: $data['tem_plano'] ?? null,
            situacaoPlano: $data['situacao_plano'] ?? null,
            dataUltimaAtualizacao: $data['data_ultima_atualizacao'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'codigoIbge' => $this->codigoIbge,
            'temPlano' => $this->temPlano,
            'situacaoPlano' => $this->situacaoPlano,
            'dataUltimaAtualizacao' => $this->dataUltimaAtualizacao,
        ];
    }
}
