<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Enums;

use Carbon\CarbonInterface;

/**
 * Status derivado a partir de data_validade vs hoje.
 *
 * Computado pelo accessor CompdecAnexo::status_validade.
 * - SEM_VALIDADE: data_validade null (vigencia indeterminada)
 * - VIGENTE:      data_validade > hoje + janela de alerta
 * - PROX_VENCIMENTO: hoje <= data_validade <= hoje + janela
 * - VENCIDO:      data_validade < hoje
 */
enum StatusValidade: string
{
    case SEM_VALIDADE = 'sem_validade';
    case VIGENTE = 'vigente';
    case PROX_VENCIMENTO = 'prox_vencimento';
    case VENCIDO = 'vencido';

    public function label(): string
    {
        return match ($this) {
            self::SEM_VALIDADE => 'Sem Validade',
            self::VIGENTE => 'Vigente',
            self::PROX_VENCIMENTO => 'Proximo do Vencimento',
            self::VENCIDO => 'Vencido',
        };
    }

    public static function fromDataValidade(?CarbonInterface $dataValidade, int $janelaAlertaDias): self
    {
        if (! $dataValidade) {
            return self::SEM_VALIDADE;
        }

        $hoje = now()->startOfDay();
        $vencimento = $dataValidade->copy()->startOfDay();

        if ($vencimento->lt($hoje)) {
            return self::VENCIDO;
        }

        if ($vencimento->lte($hoje->copy()->addDays($janelaAlertaDias))) {
            return self::PROX_VENCIMENTO;
        }

        return self::VIGENTE;
    }
}
