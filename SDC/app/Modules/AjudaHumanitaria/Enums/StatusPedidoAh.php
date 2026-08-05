<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Enums;

/**
 * Status do pedido de material de ajuda humanitaria.
 *
 * Fonte unica de verdade do processo (RN-13). A fase e derivada por fase().
 *
 * Matriz de transicoes (RN-12), normativa deste modulo:
 *
 *   0 EdicaoCompdec -> 1 AnaliseDlog -> 2 AnaliseDiretorDlog -> 3 Aprovado
 *   -> 4 AguardandoDisponibilidade -> 5 AguardandoRetirada -> 6 Atendido
 *   -> 9 Finalizado
 *
 *   Devolucoes: 1 -> 0 e 2 -> 1.
 *   Saidas: 7 Cancelado (de 0 a 6) e 8 Reprovado (de 1 e 2).
 *
 * As condicoes de cada transicao nao estao aqui: vivem nas guardas sob
 * Domain/Guards e sao compostas por PedidoAhWorkflow. Este enum responde
 * apenas se o caminho existe no grafo.
 */
enum StatusPedidoAh: int
{
    case EdicaoCompdec              = 0;
    case AnaliseDlog                = 1;
    case AnaliseDiretorDlog         = 2;
    case Aprovado                   = 3;
    case AguardandoDisponibilidade  = 4;
    case AguardandoRetirada         = 5;
    case Atendido                   = 6;
    case Cancelado                  = 7;
    case Reprovado                  = 8;
    case Finalizado                 = 9;

    public function label(): string
    {
        return match ($this) {
            self::EdicaoCompdec             => 'Edição COMPDEC',
            self::AnaliseDlog               => 'Análise DLOG',
            self::AnaliseDiretorDlog        => 'Análise Diretor DLOG',
            self::Aprovado                  => 'Aprovado',
            self::AguardandoDisponibilidade => 'Aguardando disponibilidade de material',
            self::AguardandoRetirada        => 'Aguardando retirada de material',
            self::Atendido                  => 'Atendido',
            self::Cancelado                 => 'Cancelado',
            self::Reprovado                 => 'Reprovado',
            self::Finalizado                => 'Processo finalizado',
        };
    }

    public function fase(): FasePedidoAh
    {
        return match ($this) {
            self::EdicaoCompdec             => FasePedidoAh::EdicaoCompdec,
            self::AnaliseDlog               => FasePedidoAh::AnaliseDlog,
            self::AnaliseDiretorDlog        => FasePedidoAh::AnaliseCoord,
            self::Aprovado                  => FasePedidoAh::Aprovado,
            self::AguardandoDisponibilidade => FasePedidoAh::AguardDisp,
            self::AguardandoRetirada        => FasePedidoAh::AguardRet,
            self::Atendido                  => FasePedidoAh::Atendido,
            self::Cancelado                 => FasePedidoAh::Cancelado,
            self::Reprovado                 => FasePedidoAh::Reprovado,
            self::Finalizado                => FasePedidoAh::Finalizado,
        };
    }

    /**
     * Classes utilitarias para o badge de status.
     *
     * A intencao semantica vem de getCorStatus do legado, convertida para
     * tokens Tailwind com suporte a tema claro e escuro.
     *
     * @return array{fundo: string, texto: string}
     */
    public function cor(): array
    {
        return match ($this) {
            self::EdicaoCompdec => [
                'fundo' => 'bg-amber-100 dark:bg-amber-900/40',
                'texto' => 'text-amber-900 dark:text-amber-100',
            ],
            self::AnaliseDlog => [
                'fundo' => 'bg-slate-100 dark:bg-slate-800',
                'texto' => 'text-slate-900 dark:text-slate-100',
            ],
            self::AnaliseDiretorDlog => [
                'fundo' => 'bg-blue-100 dark:bg-blue-900/40',
                'texto' => 'text-blue-900 dark:text-blue-100',
            ],
            self::Aprovado => [
                'fundo' => 'bg-orange-100 dark:bg-orange-900/40',
                'texto' => 'text-orange-900 dark:text-orange-100',
            ],
            self::AguardandoDisponibilidade => [
                'fundo' => 'bg-violet-100 dark:bg-violet-900/40',
                'texto' => 'text-violet-900 dark:text-violet-100',
            ],
            self::AguardandoRetirada => [
                'fundo' => 'bg-yellow-100 dark:bg-yellow-900/40',
                'texto' => 'text-yellow-900 dark:text-yellow-100',
            ],
            self::Atendido => [
                'fundo' => 'bg-green-100 dark:bg-green-900/40',
                'texto' => 'text-green-900 dark:text-green-100',
            ],
            self::Cancelado => [
                'fundo' => 'bg-red-100 dark:bg-red-900/40',
                'texto' => 'text-red-900 dark:text-red-100',
            ],
            self::Reprovado => [
                'fundo' => 'bg-gray-200 dark:bg-gray-700',
                'texto' => 'text-gray-900 dark:text-gray-100',
            ],
            self::Finalizado => [
                'fundo' => 'bg-emerald-100 dark:bg-emerald-900/40',
                'texto' => 'text-emerald-900 dark:text-emerald-100',
            ],
        };
    }

    /**
     * @return array<int, self>
     */
    public function transicoesPermitidas(): array
    {
        return match ($this) {
            self::EdicaoCompdec => [
                self::AnaliseDlog,
                self::Cancelado,
            ],
            self::AnaliseDlog => [
                self::AnaliseDiretorDlog,
                self::EdicaoCompdec,
                self::Reprovado,
                self::Cancelado,
            ],
            self::AnaliseDiretorDlog => [
                self::Aprovado,
                self::AnaliseDlog,
                self::Reprovado,
                self::Cancelado,
            ],
            self::Aprovado => [
                self::AguardandoDisponibilidade,
                self::Cancelado,
            ],
            self::AguardandoDisponibilidade => [
                self::AguardandoRetirada,
                self::Cancelado,
            ],
            self::AguardandoRetirada => [
                self::Atendido,
                self::Cancelado,
            ],
            self::Atendido => [
                self::Finalizado,
                self::Cancelado,
            ],
            self::Cancelado, self::Reprovado, self::Finalizado => [],
        };
    }

    public function podeTransitarPara(self $alvo): bool
    {
        return in_array($alvo, $this->transicoesPermitidas(), true);
    }

    public function ehTerminal(): bool
    {
        return $this->transicoesPermitidas() === [];
    }

    /**
     * @return array<int, array{value: int, label: string, fase: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $caso) => [
                'value' => $caso->value,
                'label' => $caso->label(),
                'fase'  => $caso->fase()->value,
            ],
            self::cases(),
        );
    }
}
