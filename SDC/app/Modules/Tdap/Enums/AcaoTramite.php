<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Enums;

/**
 * O que o responsavel da fase faz com o cronograma no Acompanhamento.
 * O destino de cada acao depende da fase atual (FaseCronograma::destino).
 */
enum AcaoTramite: string
{
    case Enviar   = 'enviar';
    case Aprovar  = 'aprovar';
    case Devolver = 'devolver';
    case Ativar   = 'ativar';
    // Nao e acao do painel: CronogramaService::encerrar grava o tramite.
    case Encerrar = 'encerrar';

    public function label(): string
    {
        return match ($this) {
            self::Enviar   => 'Enviar para análise',
            self::Aprovar  => 'Aprovar',
            self::Devolver => 'Devolver',
            self::Ativar   => 'Ativar cronograma',
            self::Encerrar => 'Encerrar cronograma',
        };
    }

    /** Devolver sem dizer o que corrigir deixa o responsavel anterior adivinhando. */
    public function exigeObservacao(): bool
    {
        return $this === self::Devolver;
    }

    /** Evento gravado em tdap_historicos. Ativar ja e registrado pelo CronogramaObserver. */
    public function tipoEvento(): ?string
    {
        return match ($this) {
            self::Enviar   => 'cronograma.enviado_analise',
            self::Aprovar  => 'cronograma.aprovado',
            self::Devolver => 'cronograma.devolvido',
            self::Ativar, self::Encerrar => null,
        };
    }
}
