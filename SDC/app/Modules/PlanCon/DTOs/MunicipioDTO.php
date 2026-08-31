<?php

declare(strict_types=1);

namespace App\Modules\PlanCon\DTOs;

/**
 * Uma linha da lista de cobertura.
 *
 * Na lista "com plano" cada linha e um PLANO (o legado lista todas as versoes
 * do municipio, nao so a vigente), por isso os campos de plano. Na lista "sem
 * plano" eles vem nulos.
 */
class MunicipioDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $nome,
        public readonly ?string $codigoIbge,
        public readonly ?bool $temPlano,
        public readonly ?string $situacaoPlano,
        public readonly ?string $dataUltimaAtualizacao,
        public readonly ?int $codigoMunicipio = null,
        public readonly ?int $planoId = null,
        public readonly ?string $versao = null,
        public readonly ?string $arquivo = null,
        public readonly bool $temArquivo = false,
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
            codigoMunicipio: isset($data['codigo_municipio']) ? (int) $data['codigo_municipio'] : null,
            planoId: isset($data['plano_id']) ? (int) $data['plano_id'] : null,
            versao: $data['versao'] ?? null,
            arquivo: $data['arquivo'] ?? null,
            temArquivo: (bool) ($data['tem_arquivo'] ?? false),
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
            'codigoMunicipio' => $this->codigoMunicipio,
            'planoId' => $this->planoId,
            'versao' => $this->versao,
            'arquivo' => $this->arquivo,
            'temArquivo' => $this->temArquivo,
        ];
    }
}
