<?php

declare(strict_types=1);

namespace App\Modules\Tdap\DTOs;

final readonly class CronoViagemDTO
{
    public function __construct(
        public int $crono_caminhao_id,
        public string $data_registro,
        public ?string $obs,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            crono_caminhao_id: (int) ($data['crono_caminhao_id'] ?? 0),
            data_registro:     (string) ($data['data_registro'] ?? ''),
            obs:               self::nullable($data['obs'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'crono_caminhao_id' => $this->crono_caminhao_id,
            'data_registro'     => $this->data_registro,
            'obs'               => $this->obs,
        ];
    }

    private static function nullable(mixed $value): ?string
    {
        if ($value === null) return null;
        $str = trim((string) $value);

        return $str === '' ? null : $str;
    }
}
