<?php

declare(strict_types=1);

namespace App\Modules\Tdap\DTOs;

final readonly class CaminhaoDTO
{
    public function __construct(
        public int $prestador_id,
        public string $placa,
        public ?string $marca,
        public ?string $modelo,
        public ?string $cor,
        public ?string $ano,
        public float $capacidade_m3,
        public bool $ativo,
        public ?string $observacoes,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            prestador_id:  (int) ($data['prestador_id'] ?? 0),
            placa:         mb_strtoupper((string) ($data['placa'] ?? '')),
            marca:         self::nullable($data['marca'] ?? null),
            modelo:        self::nullable($data['modelo'] ?? null),
            cor:           self::nullable($data['cor'] ?? null),
            ano:           self::nullable($data['ano'] ?? null),
            capacidade_m3: (float) ($data['capacidade_m3'] ?? 0),
            ativo:         (bool) ($data['ativo'] ?? true),
            observacoes:   self::nullable($data['observacoes'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'prestador_id'  => $this->prestador_id,
            'placa'         => $this->placa,
            'marca'         => $this->marca,
            'modelo'        => $this->modelo,
            'cor'           => $this->cor,
            'ano'           => $this->ano,
            'capacidade_m3' => $this->capacidade_m3,
            'ativo'         => $this->ativo,
            'observacoes'   => $this->observacoes,
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
