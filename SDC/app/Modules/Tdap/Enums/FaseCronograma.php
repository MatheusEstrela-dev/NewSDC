<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Enums;

/**
 * Fase de tramitacao do cronograma: com quem ele esta e o que falta.
 *
 * Mesma ideia de FasePedidoAh (AjudaHumanitaria) -- a fase diz quem precisa
 * agir -- com o grafo reduzido a 5 fases:
 *
 *   elaboracao --enviar--> em_analise --aprovar--> aprovado --ativar--> em_execucao --encerrar--> entregue
 *        ^                    |   ^                   |
 *        +-----devolver-------+   +-----devolver------+
 *
 * `entregue` (Entregues) e o cronograma encerrado: a agua foi entregue e o
 * fluxo acabou. O encerramento continua em CronogramaService::encerrar, que
 * grava a fase; nao ha botao de painel para ele.
 */
enum FaseCronograma: string
{
    case Elaboracao = 'elaboracao';
    case EmAnalise  = 'em_analise';
    case Aprovado   = 'aprovado';
    case EmExecucao = 'em_execucao';
    case Entregue   = 'entregue';

    public function label(): string
    {
        return match ($this) {
            self::Elaboracao => 'Em elaboração',
            self::EmAnalise  => 'Em análise CEDEC',
            self::Aprovado   => 'Aprovado, aguardando ativação',
            self::EmExecucao => 'Em execução',
            self::Entregue   => 'Entregues',
        };
    }

    public function responsavel(): string
    {
        return match ($this) {
            self::Elaboracao => 'Cadastro do cronograma',
            self::EmAnalise  => 'Analista CEDEC',
            self::Aprovado   => 'Gestor',
            self::EmExecucao => 'Acompanhamento das viagens',
            self::Entregue   => 'Cronograma encerrado',
        };
    }

    /**
     * Permissao do responsavel pela fase (quem "age aqui" e entra na Minha
     * fila); null = ninguem tramita. A permissao de CADA acao esta em
     * permissaoPara().
     */
    public function permissao(): ?string
    {
        return match ($this) {
            self::Elaboracao => 'tdap.cronogramas.enviar',
            self::EmAnalise  => 'tdap.cronogramas.analisar',
            self::Aprovado   => 'tdap.cronogramas.ativar',
            self::EmExecucao, self::Entregue => null,
        };
    }

    /**
     * Permissao exigida para esta acao nesta fase; null = a acao nao se aplica.
     *
     * Cada passo e de um profissional:
     *  - enviar para analise: quem cadastra o cronograma (`enviar`);
     *  - aprovar ou devolver na analise: analista CEDEC (`analisar`);
     *  - ativar ou devolver a analise: gestor (`ativar`).
     * O encerramento fica fora do painel, com slug proprio (`encerrar`).
     */
    public function permissaoPara(AcaoTramite $acao): ?string
    {
        return match ([$this, $acao]) {
            [self::Elaboracao, AcaoTramite::Enviar]  => 'tdap.cronogramas.enviar',
            [self::EmAnalise, AcaoTramite::Aprovar],
            [self::EmAnalise, AcaoTramite::Devolver] => 'tdap.cronogramas.analisar',
            [self::Aprovado, AcaoTramite::Ativar],
            [self::Aprovado, AcaoTramite::Devolver]  => 'tdap.cronogramas.ativar',
            default                                  => null,
        };
    }

    public function ordem(): int
    {
        return match ($this) {
            self::Elaboracao => 1,
            self::EmAnalise  => 2,
            self::Aprovado   => 3,
            self::EmExecucao => 4,
            self::Entregue   => 5,
        };
    }

    /** Fase para onde a acao leva; null = acao nao permitida nesta fase. */
    public function destino(AcaoTramite $acao): ?self
    {
        return match ([$this, $acao]) {
            [self::Elaboracao, AcaoTramite::Enviar]   => self::EmAnalise,
            [self::EmAnalise, AcaoTramite::Aprovar]   => self::Aprovado,
            [self::EmAnalise, AcaoTramite::Devolver]  => self::Elaboracao,
            [self::Aprovado, AcaoTramite::Ativar]     => self::EmExecucao,
            [self::Aprovado, AcaoTramite::Devolver]   => self::EmAnalise,
            default                                   => null,
        };
    }

    /** @return list<AcaoTramite> */
    public function acoesPermitidas(): array
    {
        return array_values(array_filter(
            AcaoTramite::cases(),
            fn (AcaoTramite $acao) => $this->destino($acao) !== null,
        ));
    }

    /** @return array{value: string, label: string, responsavel: string, ordem: int} */
    public function toArray(): array
    {
        return [
            'value'       => $this->value,
            'label'       => $this->label(),
            'responsavel' => $this->responsavel(),
            'ordem'       => $this->ordem(),
        ];
    }
}
