<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Enums;

/**
 * Decisao explicita tomada para cada transacao de negocio avaliada.
 *
 * Toda transacao recebe uma decisao registrada: nao existe fato avaliado sem
 * resultado. Zero e apuracao sao decisoes, nao ausencia de decisao.
 */
enum DecisaoPontuacao: string
{
    /** Credito reconhecido e somado ao saldo. */
    case Confirmada = 'confirmada';

    /** Credito reconhecido, aguardando validacao do marco. Nao soma ao saldo. */
    case Pendente = 'pendente';

    /** Fato avaliado sem direito a premio, com motivo registrado. */
    case Zero = 'zero';

    /** Autoria ou vinculo nao reconstruiveis. Fica fora da classificacao. */
    case EmApuracao = 'em_apuracao';

    /** Credito retirado por invalidacao, cancelamento ou correcao. */
    case Estornada = 'estornada';

    public function label(): string
    {
        return match ($this) {
            self::Confirmada => 'Confirmada',
            self::Pendente   => 'Pendente de validacao',
            self::Zero       => 'Sem pontuacao',
            self::EmApuracao => 'Em apuracao',
            self::Estornada  => 'Estornada',
        };
    }

    /**
     * Apenas decisoes confirmadas compoem o saldo do placar.
     */
    public function somaAoSaldo(): bool
    {
        return $this === self::Confirmada;
    }

    /**
     * Decisoes terminais nao podem ser reavaliadas sem lancamento corretivo.
     */
    public function ehTerminal(): bool
    {
        return match ($this) {
            self::Confirmada, self::Zero, self::Estornada => true,
            self::Pendente, self::EmApuracao              => false,
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $c) => ['value' => $c->value, 'label' => $c->label()],
            self::cases(),
        );
    }
}
