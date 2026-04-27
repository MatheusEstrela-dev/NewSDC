<?php

declare(strict_types=1);

namespace App\Modules\Rat\DTOs;

/**
 * DTO para dados do histórico e descrição da ocorrência RAT.
 */
readonly class RatHistoricoDTO
{
    public function __construct(
        public ?string $historico = null,
        public ?array  $clima = null,
        public ?string $resultado = null,
        public ?string $grauRisco = null,
        public ?array  $metricas = null,
        public ?string $encaminhamentos = null,
    ) {}

    public static function fromArray(array $data): self
    {
        $historico = $data['historico'] ?? $data['descricao'] ?? null;
        if (is_array($historico)) {
            $historico = null;
        }

        return new self(
            historico:        $historico,
            clima:            $data['clima']            ?? null,
            resultado:        $data['resultado']        ?? null,
            grauRisco:        $data['grau_risco']       ?? null,
            metricas:         $data['metricas']         ?? null,
            encaminhamentos:  $data['encaminhamentos']  ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'historico'       => $this->historico,
            'clima'           => $this->clima,
            'resultado'       => $this->resultado,
            'grau_risco'      => $this->grauRisco,
            'metricas'        => $this->metricas,
            'encaminhamentos' => $this->encaminhamentos,
        ], fn ($v) => $v !== null);
    }
}
