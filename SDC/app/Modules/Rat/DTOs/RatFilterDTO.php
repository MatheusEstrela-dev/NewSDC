<?php

declare(strict_types=1);

namespace App\Modules\Rat\DTOs;

readonly class RatFilterDTO
{
    public function __construct(
        public readonly ?string $protocolo  = null,
        public readonly ?string $dataInicio = null,
        public readonly ?string $dataFim    = null,
        public readonly ?string $ano        = null,
        public readonly ?string $municipio  = null,
        public readonly ?string $status     = null,
        public readonly ?string $tipoCobrade = null,
        public readonly ?string $natureza   = null,
        public readonly ?string $criadoPor  = null,
        public readonly int     $perPage    = 15,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            protocolo:   $data['protocolo']    ?? null,
            dataInicio:  $data['data_inicio']  ?? null,
            dataFim:     $data['data_fim']     ?? null,
            ano:         $data['ano']          ?? null,
            municipio:   $data['municipio']    ?? null,
            status:      $data['status']       ?? null,
            tipoCobrade: $data['tipo_cobrade'] ?? null,
            natureza:    $data['natureza']     ?? null,
            criadoPor:   $data['criado_por']   ?? null,
            perPage:     (int) ($data['per_page'] ?? 15),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'protocolo'   => $this->protocolo,
            'data_inicio' => $this->dataInicio,
            'data_fim'    => $this->dataFim,
            'ano'         => $this->ano,
            'municipio'   => $this->municipio,
            'status'      => $this->status,
            'tipo_cobrade'=> $this->tipoCobrade,
            'natureza'    => $this->natureza,
            'criado_por'  => $this->criadoPor,
        ]);
    }
}
