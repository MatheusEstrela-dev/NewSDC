<?php

declare(strict_types=1);

namespace App\Modules\Tdap\DTOs;

use App\Modules\Tdap\Enums\EstadoProcesso;

final readonly class TransicaoProcessoDTO
{
    public function __construct(
        public EstadoProcesso $estadoAlvo,
        public ?string $motivo,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            estadoAlvo: EstadoProcesso::from((string) ($data['estado_alvo'] ?? '')),
            motivo:     self::nullable($data['motivo'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'estado_alvo' => $this->estadoAlvo->value,
            'motivo'      => $this->motivo,
        ];
    }

    private static function nullable(mixed $value): ?string
    {
        if ($value === null) return null;
        $str = trim((string) $value);

        return $str === '' ? null : $str;
    }
}
