<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Enums;

enum FuncaoEquipe: string
{
    case COORDENADOR = 'coordenador';
    case AGENTE = 'agente';
    case TECNICO = 'tecnico';
    case APOIO = 'apoio';
    case OUTRO = 'outro';

    public function label(): string
    {
        return match ($this) {
            self::COORDENADOR => 'Coordenador',
            self::AGENTE => 'Agente',
            self::TECNICO => 'Tecnico',
            self::APOIO => 'Apoio',
            self::OUTRO => 'Outro',
        };
    }

    /**
     * Funcoes que contam como "efetivo" do orgao (alimentam qtd_efetivo via Observer).
     * Apoio e Outro nao contam — sao colaboracoes pontuais.
     */
    public function contaParaEfetivo(): bool
    {
        return match ($this) {
            self::COORDENADOR, self::AGENTE, self::TECNICO => true,
            self::APOIO, self::OUTRO => false,
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
