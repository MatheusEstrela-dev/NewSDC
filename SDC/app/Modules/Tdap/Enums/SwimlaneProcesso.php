<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Enums;

/**
 * Atores/raias do fluxograma TDAP.
 *
 * Cada estado do processo aponta para a swimlane responsavel pela proxima
 * acao (quem deve "pegar a bola" agora).
 */
enum SwimlaneProcesso: string
{
    case Fomentacao = 'fomentacao';
    case Cedec      = 'cedec';
    case Juridico   = 'juridico';
    case Licitacao  = 'licitacao';
    case Governador = 'governador';
    case Financeiro = 'financeiro';
    case Encerrado  = 'encerrado';

    public function label(): string
    {
        return match ($this) {
            self::Fomentacao => 'Fomentação / Município',
            self::Cedec      => 'CEDEC',
            self::Juridico   => 'Diretoria Jurídica',
            self::Licitacao  => 'Órgão de Licitação',
            self::Governador => 'Governador',
            self::Financeiro => 'Financeiro',
            self::Encerrado  => 'Encerrado',
        };
    }

    public function cor(): string
    {
        return match ($this) {
            self::Fomentacao => 'sky',
            self::Cedec      => 'blue',
            self::Juridico   => 'purple',
            self::Licitacao  => 'amber',
            self::Governador => 'rose',
            self::Financeiro => 'emerald',
            self::Encerrado  => 'slate',
        };
    }

    /**
     * @return array<int, array{value: string, label: string, cor: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $s) => ['value' => $s->value, 'label' => $s->label(), 'cor' => $s->cor()],
            self::cases(),
        );
    }
}
