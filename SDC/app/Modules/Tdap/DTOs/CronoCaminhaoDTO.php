<?php

declare(strict_types=1);

namespace App\Modules\Tdap\DTOs;

final readonly class CronoCaminhaoDTO
{
    public function __construct(
        public int $cronograma_id,
        public int $caminhao_id,
        public ?int $comunidade_id,
        public float $agua_prevista,
        public int $num_viagens,
        public int $ordem,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            cronograma_id: (int) ($data['cronograma_id'] ?? 0),
            caminhao_id:   (int) ($data['caminhao_id'] ?? 0),
            comunidade_id: isset($data['comunidade_id']) && $data['comunidade_id'] !== '' ? (int) $data['comunidade_id'] : null,
            agua_prevista: (float) ($data['agua_prevista'] ?? 0),
            num_viagens:   (int) ($data['num_viagens'] ?? 0),
            ordem:         (int) ($data['ordem'] ?? 0),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'cronograma_id' => $this->cronograma_id,
            'caminhao_id'   => $this->caminhao_id,
            'comunidade_id' => $this->comunidade_id,
            'agua_prevista' => $this->agua_prevista,
            'num_viagens'   => $this->num_viagens,
            'ordem'         => $this->ordem,
        ];
    }
}
