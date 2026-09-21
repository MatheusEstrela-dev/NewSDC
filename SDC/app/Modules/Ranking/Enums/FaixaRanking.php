<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Enums;

/**
 * Faixa de atividade do participante, derivada do saldo confirmado.
 *
 * Nao confundir com as camadas bronze/silver/gold do modulo Medalhao, que sao
 * schemas de dados (arquitetura medallion) e nao tem relacao com pontuacao.
 *
 * Os limiares sao de atividade, nao de conformidade: o IPCM e apurado em
 * separado e nunca altera a faixa.
 */
enum FaixaRanking: string
{
    case Bronze = 'bronze';
    case Prata = 'prata';
    case Ouro = 'ouro';
    case Diamante = 'diamante';

    public function label(): string
    {
        return match ($this) {
            self::Bronze   => 'Bronze',
            self::Prata    => 'Prata',
            self::Ouro     => 'Ouro',
            self::Diamante => 'Diamante',
        };
    }

    /**
     * Saldo minimo (inclusivo) para entrar na faixa.
     */
    public function pontosMinimos(): int
    {
        return match ($this) {
            self::Bronze   => 0,
            self::Prata    => 300,
            self::Ouro     => 700,
            self::Diamante => 1500,
        };
    }

    /**
     * Saldo maximo (inclusivo) da faixa. Null na faixa topo, que nao tem teto.
     */
    public function pontosMaximos(): ?int
    {
        return match ($this) {
            self::Bronze   => 299,
            self::Prata    => 699,
            self::Ouro     => 1499,
            self::Diamante => null,
        };
    }

    /**
     * Resolve a faixa a partir do saldo confirmado.
     *
     * Saldo negativo (estorno maior que o credito acumulado no periodo) cai em
     * Bronze: a faixa e de atividade reconhecida, nao de penalidade.
     */
    public static function deSaldo(int $pontos): self
    {
        return match (true) {
            $pontos >= self::Diamante->pontosMinimos() => self::Diamante,
            $pontos >= self::Ouro->pontosMinimos()     => self::Ouro,
            $pontos >= self::Prata->pontosMinimos()    => self::Prata,
            default                                    => self::Bronze,
        };
    }

    /**
     * Pontos que faltam para a proxima faixa. Null quando ja esta no topo.
     */
    public function pontosParaProxima(int $saldoAtual): ?int
    {
        $proxima = $this->proxima();

        if ($proxima === null) {
            return null;
        }

        return max(0, $proxima->pontosMinimos() - $saldoAtual);
    }

    public function proxima(): ?self
    {
        return match ($this) {
            self::Bronze   => self::Prata,
            self::Prata    => self::Ouro,
            self::Ouro     => self::Diamante,
            self::Diamante => null,
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
