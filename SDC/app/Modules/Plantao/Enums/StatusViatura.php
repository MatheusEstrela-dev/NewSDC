<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Enums;

enum StatusViatura: string
{
    case DISPONIVEL = 'DISPONIVEL';
    case EM_TRANSITO = 'EM_TRANSITO';
    case MANUTENCAO = 'MANUTENCAO';
    case CEDIDA = 'CEDIDA';
    case INDISPONIVEL = 'INDISPONIVEL';

    public function label(): string
    {
        return match ($this) {
            self::DISPONIVEL => 'Disponivel',
            self::EM_TRANSITO => 'Em transito',
            self::MANUTENCAO => 'Manutencao',
            self::CEDIDA => 'Cedida',
            self::INDISPONIVEL => 'Indisponivel',
        };
    }

    /**
     * Entra na listagem de "viaturas em condicoes de atendimento" do relatorio.
     * Viatura em transito continua em condicoes: ela esta rodando, nao avariada.
     */
    public function emCondicoes(): bool
    {
        return match ($this) {
            self::DISPONIVEL, self::EM_TRANSITO => true,
            self::MANUTENCAO, self::CEDIDA, self::INDISPONIVEL => false,
        };
    }

    /**
     * Pode iniciar uma nova movimentacao. Em transito nao pode: ja esta fora.
     */
    public function podeSair(): bool
    {
        return $this === self::DISPONIVEL;
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
