<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Enums;

/**
 * Estados do agregado ProcessoTdap.
 *
 * Maquina de estados (transitions definidas em ProcessoTdapState::canTransitTo):
 *
 *   RASCUNHO -> EM_HABILITACAO -> DECRETO_PENDENTE -> EM_LICITACAO ->
 *   EM_EXECUCAO -> LIQUIDACAO_PENDENTE -> PAGO -> ENCERRADO
 *
 * Reprovacoes podem voltar ao estado anterior (EM_HABILITACAO -> RASCUNHO etc.).
 */
enum EstadoProcesso: string
{
    case Rascunho            = 'rascunho';
    case EmHabilitacao       = 'em_habilitacao';
    case DecretoPendente     = 'decreto_pendente';
    case EmLicitacao         = 'em_licitacao';
    case EmExecucao          = 'em_execucao';
    case LiquidacaoPendente  = 'liquidacao_pendente';
    case Pago                = 'pago';
    case Encerrado           = 'encerrado';

    public function label(): string
    {
        return match ($this) {
            self::Rascunho           => 'Rascunho',
            self::EmHabilitacao      => 'Em habilitação',
            self::DecretoPendente    => 'Aguardando decreto',
            self::EmLicitacao        => 'Em licitação',
            self::EmExecucao         => 'Em execução',
            self::LiquidacaoPendente => 'Aguardando liquidação',
            self::Pago               => 'Pago',
            self::Encerrado          => 'Encerrado',
        };
    }

    public function swimlane(): SwimlaneProcesso
    {
        return match ($this) {
            self::Rascunho           => SwimlaneProcesso::Fomentacao,
            self::EmHabilitacao      => SwimlaneProcesso::Cedec,
            self::DecretoPendente    => SwimlaneProcesso::Governador,
            self::EmLicitacao        => SwimlaneProcesso::Licitacao,
            self::EmExecucao         => SwimlaneProcesso::Cedec,
            self::LiquidacaoPendente => SwimlaneProcesso::Financeiro,
            self::Pago               => SwimlaneProcesso::Financeiro,
            self::Encerrado          => SwimlaneProcesso::Encerrado,
        };
    }

    /**
     * Define transicoes legais (fluxo principal + reprovacoes).
     *
     * @return array<int, self>
     */
    public function transicoesPermitidas(): array
    {
        return match ($this) {
            self::Rascunho           => [self::EmHabilitacao],
            self::EmHabilitacao      => [self::DecretoPendente, self::Rascunho], // pode reprovar
            self::DecretoPendente    => [self::EmLicitacao, self::EmHabilitacao],
            self::EmLicitacao        => [self::EmExecucao, self::DecretoPendente],
            self::EmExecucao         => [self::LiquidacaoPendente],
            self::LiquidacaoPendente => [self::Pago, self::EmExecucao], // pode pedir ajuste
            self::Pago               => [self::Encerrado],
            self::Encerrado          => [], // estado terminal
        };
    }

    public function podeTransitarPara(self $alvo): bool
    {
        foreach ($this->transicoesPermitidas() as $permitido) {
            if ($permitido === $alvo) return true;
        }

        return false;
    }

    /**
     * @return array<int, array{value: string, label: string, swimlane: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $e) => [
                'value'    => $e->value,
                'label'    => $e->label(),
                'swimlane' => $e->swimlane()->value,
            ],
            self::cases(),
        );
    }
}
