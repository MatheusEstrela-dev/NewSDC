<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Enums;

/**
 * Situacao da conta no AD vista pelo SDC (coluna-espelho status_ad). Nao se
 * confunde com o status local de aprovacao do cadastro.
 */
enum StatusAd: string
{
    case DESCONHECIDO = 'desconhecido';
    case ATIVA = 'ativa';
    case DESABILITADA = 'desabilitada';
    case NAO_ENCONTRADA = 'nao_encontrada';
    case FORA_DO_ESCOPO = 'fora_do_escopo';
    case PROTEGIDA = 'protegida';

    public function label(): string
    {
        return match ($this) {
            self::DESCONHECIDO => 'Não consultada',
            self::ATIVA => 'Ativa',
            self::DESABILITADA => 'Desabilitada',
            self::NAO_ENCONTRADA => 'Não encontrada no AD',
            self::FORA_DO_ESCOPO => 'Fora da unidade gerenciada',
            self::PROTEGIDA => 'Protegida',
        };
    }
}
