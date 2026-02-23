<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Application\DTOs;

use Illuminate\Contracts\Support\Arrayable;

/**
 * ViewModel: Estatísticas de Decretações
 * Implementa Arrayable para conversão automática pelo Inertia
 */
final class DecretacoesStatisticsDTO implements Arrayable
{
    public function __construct(
        public readonly int $totalEventos,
        public readonly int $totalEcp,
        public readonly int $totalSe,
        public readonly int $registros,
        public readonly int $registrosEcp,
        public readonly int $registrosSe,
        public readonly int $decretacoes,
        public readonly int $decretacoesEcp,
        public readonly int $decretacoesSe,
        public readonly int $municipiosAtingidos,
        public readonly int $municipiosEcp,
        public readonly int $municipiosSe,
        public readonly int $decretacoesVigentes,
        public readonly int $vigentesEcp,
        public readonly int $vigentesSe,
    ) {
    }

    public static function empty(): self
    {
        return new self(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
    }

    public static function fromCounts(int $total, int $ecp = 0, int $se = 0): self
    {
        return new self(
            totalEventos: $total,
            totalEcp: $ecp,
            totalSe: $se,
            registros: $total,
            registrosEcp: $ecp,
            registrosSe: $se,
            decretacoes: $total,
            decretacoesEcp: $ecp,
            decretacoesSe: $se,
            municipiosAtingidos: $total,
            municipiosEcp: $ecp,
            municipiosSe: $se,
            decretacoesVigentes: $total,
            vigentesEcp: $ecp,
            vigentesSe: $se,
        );
    }

    /**
     * Arrayable: Conversão automática pelo Inertia
     */
    public function toArray(): array
    {
        return [
            'total_eventos' => $this->totalEventos,
            'total_ecp' => $this->totalEcp,
            'total_se' => $this->totalSe,
            'registros' => $this->registros,
            'registros_ecp' => $this->registrosEcp,
            'registros_se' => $this->registrosSe,
            'decretacoes' => $this->decretacoes,
            'decretacoes_ecp' => $this->decretacoesEcp,
            'decretacoes_se' => $this->decretacoesSe,
            'municipios_atingidos' => $this->municipiosAtingidos,
            'municipios_ecp' => $this->municipiosEcp,
            'municipios_se' => $this->municipiosSe,
            'decretacoes_vigentes' => $this->decretacoesVigentes,
            'vigentes_ecp' => $this->vigentesEcp,
            'vigentes_se' => $this->vigentesSe,
        ];
    }
}
