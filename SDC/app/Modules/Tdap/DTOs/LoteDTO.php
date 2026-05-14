<?php

declare(strict_types=1);

namespace App\Modules\Tdap\DTOs;

final readonly class LoteDTO
{
    public function __construct(
        public int $ata_id,
        public int $municipio_id,
        public int $prestador_id,
        public string $numero,
        public ?string $nome,
        public float $qtd_agua_m3,
        public float $valor_m3,
        public bool $ativo,
        public ?string $observacoes,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            ata_id:       (int) ($data['ata_id'] ?? 0),
            municipio_id: (int) ($data['municipio_id'] ?? 0),
            prestador_id: (int) ($data['prestador_id'] ?? 0),
            numero:       mb_strtoupper(trim((string) ($data['numero'] ?? ''))),
            nome:         self::nullable($data['nome'] ?? null),
            qtd_agua_m3:  (float) ($data['qtd_agua_m3'] ?? 0),
            valor_m3:     (float) ($data['valor_m3'] ?? 0),
            ativo:        (bool) ($data['ativo'] ?? true),
            observacoes:  self::nullable($data['observacoes'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'ata_id'       => $this->ata_id,
            'municipio_id' => $this->municipio_id,
            'prestador_id' => $this->prestador_id,
            'numero'       => $this->numero,
            'nome'         => $this->nome,
            'qtd_agua_m3'  => $this->qtd_agua_m3,
            'valor_m3'     => $this->valor_m3,
            'ativo'        => $this->ativo,
            'observacoes'  => $this->observacoes,
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
