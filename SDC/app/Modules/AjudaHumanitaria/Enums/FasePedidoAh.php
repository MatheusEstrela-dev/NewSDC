<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Enums;

/**
 * Fase do pedido, derivada do status (RN-13).
 *
 * No legado, fase e status eram duas colunas gravadas em paralelo e podiam
 * divergir. Aqui a fase nao e armazenada: e sempre obtida de
 * StatusPedidoAh::fase(). Os slugs sao preservados do legado
 * (aju_h_pedido_pedid.tramit) para leitura de dados historicos.
 */
enum FasePedidoAh: string
{
    case EdicaoCompdec = 'edicao_compdec';
    case AnaliseDlog   = 'analise_dlog';
    case AnaliseCoord  = 'analise_coord';
    case Aprovado      = 'aprovado';
    case AguardDisp    = 'aguard_disp';
    case AguardRet     = 'aguard_ret';
    case Atendido      = 'atendido';
    case Cancelado     = 'cancelado';
    case Reprovado     = 'reprovado';
    case Finalizado    = 'finalizado';

    public function label(): string
    {
        return match ($this) {
            self::EdicaoCompdec => 'Em edição pelo COMPDEC',
            self::AnaliseDlog   => 'Em análise DLOG',
            self::AnaliseCoord  => 'Em análise do Diretor DLOG',
            self::Aprovado      => 'Processo aprovado',
            self::AguardDisp    => 'Aguardando disponibilidade de material',
            self::AguardRet     => 'Aguardando retirada de material',
            self::Atendido      => 'Em prestação de contas',
            self::Cancelado     => 'Processo cancelado',
            self::Reprovado     => 'Processo reprovado',
            self::Finalizado    => 'Processo finalizado',
        };
    }
}
