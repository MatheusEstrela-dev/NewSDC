<?php

declare(strict_types=1);

namespace App\Modules\Tdap\DTOs;

final readonly class CronogramaDTO
{
    public function __construct(
        public string $numero,
        public ?string $empenho,
        public int $ata_id,
        public int $lote_id,
        public int $municipio_id,
        public int $prestador_id,
        public ?string $cnpj,
        public float $consumo_diario,
        public int $dias,
        public float $fator,
        public string $dt_inicio,
        public string $dt_final,
        public ?string $dt_inicio_prorrogacao,
        public ?string $dt_final_prorrogacao,
        public ?string $justificativa,
        public ?string $nota_empenho,
        public ?int $ponto_captacao_id,
        public ?string $observacao,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            numero:                mb_strtoupper(trim((string) ($data['numero'] ?? ''))),
            empenho:               self::nullable($data['empenho'] ?? null),
            ata_id:                (int) ($data['ata_id'] ?? 0),
            lote_id:               (int) ($data['lote_id'] ?? 0),
            municipio_id:          (int) ($data['municipio_id'] ?? 0),
            prestador_id:          (int) ($data['prestador_id'] ?? 0),
            cnpj:                  self::nullable($data['cnpj'] ?? null),
            consumo_diario:        (float) ($data['consumo_diario'] ?? 0),
            dias:                  (int) ($data['dias'] ?? 0),
            fator:                 (float) ($data['fator'] ?? 1),
            dt_inicio:             (string) ($data['dt_inicio'] ?? ''),
            dt_final:              (string) ($data['dt_final'] ?? ''),
            dt_inicio_prorrogacao: self::nullable($data['dt_inicio_prorrogacao'] ?? null),
            dt_final_prorrogacao:  self::nullable($data['dt_final_prorrogacao'] ?? null),
            justificativa:         self::nullable($data['justificativa'] ?? null),
            nota_empenho:          self::nullable($data['nota_empenho'] ?? null),
            ponto_captacao_id:     isset($data['ponto_captacao_id']) && $data['ponto_captacao_id'] !== '' ? (int) $data['ponto_captacao_id'] : null,
            observacao:            self::nullable($data['observacao'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'numero'                => $this->numero,
            'empenho'               => $this->empenho,
            'ata_id'                => $this->ata_id,
            'lote_id'               => $this->lote_id,
            'municipio_id'          => $this->municipio_id,
            'prestador_id'          => $this->prestador_id,
            'cnpj'                  => $this->cnpj,
            'consumo_diario'        => $this->consumo_diario,
            'dias'                  => $this->dias,
            'fator'                 => $this->fator,
            'dt_inicio'             => $this->dt_inicio,
            'dt_final'              => $this->dt_final,
            'dt_inicio_prorrogacao' => $this->dt_inicio_prorrogacao,
            'dt_final_prorrogacao'  => $this->dt_final_prorrogacao,
            'justificativa'         => $this->justificativa,
            'nota_empenho'          => $this->nota_empenho,
            'ponto_captacao_id'     => $this->ponto_captacao_id,
            'observacao'            => $this->observacao,
        ];
    }

    private static function nullable(mixed $value): ?string
    {
        if ($value === null) return null;
        $str = trim((string) $value);

        return $str === '' ? null : $str;
    }
}
