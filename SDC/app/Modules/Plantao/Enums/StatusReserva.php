<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Enums;

/**
 * Ciclo de vida da reserva de viatura.
 *
 *   AGENDADA --check-in--> EM_USO --check-out--> CONCLUIDA
 *      |                      |
 *      |                      +--> (nao cancela: a viatura ja saiu)
 *      +--> CANCELADA (ato humano)
 *      +--> EXPIRADA  (plantao:expirar-reservas, janela vencida sem check-in)
 *
 * CANCELADA e EXPIRADA existem separadas de proposito: a primeira responde
 * "alguem desistiu", a segunda "ninguem apareceu". Fundir as duas apagaria o
 * indicador de reserva fantasma, que e o que justifica cobrar a agenda.
 */
enum StatusReserva: string
{
    case AGENDADA = 'AGENDADA';
    case EM_USO = 'EM_USO';
    case CONCLUIDA = 'CONCLUIDA';
    case CANCELADA = 'CANCELADA';
    case EXPIRADA = 'EXPIRADA';

    public function label(): string
    {
        return match ($this) {
            self::AGENDADA => 'Agendada',
            self::EM_USO => 'Em uso',
            self::CONCLUIDA => 'Concluida',
            self::CANCELADA => 'Cancelada',
            self::EXPIRADA => 'Expirada',
        };
    }

    /**
     * Ocupa a agenda da viatura. So estes dois disputam janela de horario:
     * uma reserva cancelada nao impede outra pessoa de pegar o mesmo carro.
     */
    public function ocupaAgenda(): bool
    {
        return match ($this) {
            self::AGENDADA, self::EM_USO => true,
            self::CONCLUIDA, self::CANCELADA, self::EXPIRADA => false,
        };
    }

    /**
     * Encerrada: nao aceita mais nenhuma transicao.
     */
    public function encerrada(): bool
    {
        return !$this->ocupaAgenda();
    }

    /**
     * Cancelar so faz sentido antes da chave sair. Depois do check-in o ato
     * correto e o check-out, que fecha a movimentacao junto.
     */
    public function podeCancelar(): bool
    {
        return $this === self::AGENDADA;
    }

    public static function toSelectArray(): array
    {
        return array_map(
            fn(self $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            self::cases()
        );
    }
}
