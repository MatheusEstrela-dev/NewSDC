<?php

declare(strict_types=1);

namespace App\Modules\Tdap\DTOs;

final readonly class LoteDTO
{
    /**
     * @param  array<int, int>  $municipio_ids  municipios atendidos pelo lote
     * @param  ?int  $municipio_id  municipio de referencia (coluna legada)
     */
    public function __construct(
        public int $ata_id,
        public array $municipio_ids,
        public ?int $municipio_id,
        public int $prestador_id,
        public string $numero,
        public ?string $nome,
        public ?string $contrato,
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
        $municipioIds = array_values(array_unique(array_map(
            'intval',
            (array) ($data['municipio_ids'] ?? []),
        )));

        return new self(
            ata_id:        (int) ($data['ata_id'] ?? 0),
            municipio_ids: $municipioIds,
            // Municipio de referencia: o primeiro da lista. Mantem a coluna
            // legada coerente sem exigir uma escolha extra do usuario.
            municipio_id:  $municipioIds[0] ?? null,
            prestador_id:  (int) ($data['prestador_id'] ?? 0),
            numero:        mb_strtoupper(trim((string) ($data['numero'] ?? ''))),
            nome:          self::nullable($data['nome'] ?? null),
            contrato:      self::nullable($data['contrato'] ?? null),
            qtd_agua_m3:   (float) ($data['qtd_agua_m3'] ?? 0),
            valor_m3:      (float) ($data['valor_m3'] ?? 0),
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
            'ata_id'       => $this->ata_id,
            'municipio_id' => $this->municipio_id,
            'prestador_id' => $this->prestador_id,
            'numero'       => $this->numero,
            'nome'         => $this->nome,
            'contrato'     => $this->contrato,
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
