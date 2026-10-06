<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Enums;

/**
 * Maquina de estados da operacao no AD: solicitado -> enviado -> confirmado|falhou,
 * enviado -> enviado (retry) e solicitado -> falhou. Estados finais nao mudam.
 */
enum EstadoOperacaoAd: string
{
    case SOLICITADO = 'solicitado';
    case ENVIADO = 'enviado';
    case CONFIRMADO = 'confirmado';
    case FALHOU = 'falhou';

    public function label(): string
    {
        return match ($this) {
            self::SOLICITADO => 'Solicitada',
            self::ENVIADO => 'Enviada ao AD',
            self::CONFIRMADO => 'Confirmada pelo AD',
            self::FALHOU => 'Falhou',
        };
    }

    public function final(): bool
    {
        return $this === self::CONFIRMADO || $this === self::FALHOU;
    }

    public function podeIrPara(self $novo): bool
    {
        return match ($this) {
            self::SOLICITADO => $novo === self::ENVIADO || $novo === self::FALHOU,
            self::ENVIADO => $novo === self::ENVIADO || $novo->final(),
            self::CONFIRMADO, self::FALHOU => false,
        };
    }
}
