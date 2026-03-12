<?php

declare(strict_types=1);

namespace App\Modules\Rat\DTO;

/**
 * DTO de entrada para criação/atualização de uma Ocorrência RAT (rat_ocorrencias).
 *
 * Representa os dados validados pelo UpdateRatRequest antes de chegarem ao Service,
 * garantindo tipagem estrita entre a camada HTTP e a camada de domínio.
 */
readonly class RatBoDTO
{
    public function __construct(
        public ?string $numeroBos           = null,
        public ?string $historico           = null,
        public ?string $prazoEdicao         = null,
        public ?int    $ocorrenciaOrigemId  = null,
        public ?int    $status              = null,
    ) {}

    /**
     * Cria o DTO a partir do array retornado por FormRequest::validated().
     */
    public static function fromArray(array $data): self
    {
        return new self(
            numeroBos:          $data['numero_bos']           ?? null,
            historico:          $data['historico']            ?? null,
            prazoEdicao:        $data['prazo_edicao']         ?? null,
            ocorrenciaOrigemId: isset($data['ocorrencia_origem_id'])
                                    ? (int) $data['ocorrencia_origem_id']
                                    : null,
            status:             isset($data['status']) ? (int) $data['status'] : null,
        );
    }

    /**
     * Converte para array (usado pelo Eloquent em create/update).
     * Campos nulos são excluídos para não sobrescrever defaults do banco.
     */
    public function toArray(): array
    {
        return array_filter([
            'numero_bos'           => $this->numeroBos,
            'historico'            => $this->historico,
            'prazo_edicao'         => $this->prazoEdicao,
            'ocorrencia_origem_id' => $this->ocorrenciaOrigemId,
            'status'               => $this->status,
        ], fn ($v) => $v !== null);
    }
}
