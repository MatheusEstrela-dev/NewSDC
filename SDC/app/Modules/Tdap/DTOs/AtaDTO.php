<?php

declare(strict_types=1);

namespace App\Modules\Tdap\DTOs;

final readonly class AtaDTO
{
    public function __construct(
        public string $numero,
        public string $dt_inicio,
        public string $dt_final,
        public ?string $historico,
        public bool $ativo,
        public ?string $observacoes,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            numero:      mb_strtoupper(trim((string) ($data['numero'] ?? ''))),
            dt_inicio:   (string) ($data['dt_inicio'] ?? ''),
            dt_final:    (string) ($data['dt_final'] ?? ''),
            historico:   self::nullable($data['historico'] ?? null),
            ativo:       (bool) ($data['ativo'] ?? true),
            observacoes: self::nullable($data['observacoes'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'numero'      => $this->numero,
            'dt_inicio'   => $this->dt_inicio,
            'dt_final'    => $this->dt_final,
            'historico'   => $this->historico,
            'ativo'       => $this->ativo,
            'observacoes' => $this->observacoes,
        ];
    }

    private static function nullable(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $str = trim((string) $value);

        return $str === '' ? null : $str;
    }
}
