<?php

declare(strict_types=1);

namespace App\Modules\Rat\DTOs;

use App\Modules\Shared\DTOs\Traits\SafeCastingTrait;

/**
 * DTO de entrada para criação/atualização de um Boletim de Ocorrência (BO).
 *
 * Representa os dados validados por BoRequest antes de chegarem ao Service,
 * garantindo tipagem estrita entre a camada HTTP e a camada de domínio.
 */
readonly class RatBoDTO
{
    use SafeCastingTrait;

    public function __construct(
        public ?string $numeroBos           = null,
        public ?string $historico           = null,
        public ?string $prazoEdicao         = null,
        // UUID da ocorrencia de origem: o (int) transformava "0199..." em 199.
        public ?string $ocorrenciaOrigemId  = null,
        public ?int    $status              = null,
        public ?int    $criadoPor           = null,
    ) {}

    /**
     * Cria o DTO a partir do array retornado por FormRequest::validated().
     */
    public static function fromArray(array $data): self
    {
        return new self(
            numeroBos:          self::asStringOrNull($data['numero_bos'] ?? null),
            historico:          self::asStringOrNull($data['historico'] ?? null),
            prazoEdicao:        self::asStringOrNull($data['prazo_edicao'] ?? null),
            ocorrenciaOrigemId: self::asUuidOrNull($data['ocorrencia_origem_id'] ?? null),
            status:             self::asIntOrNull($data['status'] ?? null),
            criadoPor:          self::asIntOrNull($data['created_by'] ?? null),
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
            'created_by'           => $this->criadoPor,
        ], fn ($v) => $v !== null);
    }
}
