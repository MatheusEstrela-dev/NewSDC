<?php

declare(strict_types=1);

namespace App\Modules\Rat\DTOs;

readonly class RatHistoricoDTO
{
    public function __construct(
        public ?string $historico = null,
        public ?array  $historicoArray = null,
        public ?array  $clima = null,
        public ?string $resultado = null,
        public ?string $grauRisco = null,
        public ?array  $metricas = null,
        public ?string $encaminhamentos = null,
    ) {}

    public static function fromArray(array $data): self
    {
        // Caso 1: o array inteiro é uma lista de eventos [{id, tipo, texto, created_at}, ...]
        // Detecta se é array indexado (lista) vs array associativo (objeto único)
        if (array_is_list($data)) {
            return new self(historicoArray: $data);
        }

        $historico = $data['historico'] ?? $data['descricao'] ?? null;

        if (is_array($historico)) {
            if (isset($historico['historico'])) {
                return new self(
                    historico:       $historico['historico'] ?? null,
                    historicoArray:  $historico['historico_array'] ?? null,
                    clima:           $historico['clima'] ?? null,
                    resultado:       $historico['resultado'] ?? null,
                    grauRisco:       $historico['grau_risco'] ?? null,
                    metricas:        $historico['metricas'] ?? null,
                    encaminhamentos: $historico['encaminhamentos'] ?? null,
                );
            }
            // historico é uma lista de eventos
            return new self(historicoArray: $historico);
        }

        return new self(
            historico:       $historico,
            clima:           $data['clima']           ?? null,
            resultado:       $data['resultado']       ?? null,
            grauRisco:       $data['grau_risco']      ?? $data['grauRisco'] ?? null,
            metricas:        $data['metricas']        ?? null,
            encaminhamentos: $data['encaminhamentos'] ?? null,
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
