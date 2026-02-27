<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Enums;

enum StatusProcesso: string
{
    case REGISTRO = 'registro';
    case AGUARDANDO_ANALISE_ESTADO = 'aguardando_analise_estado';
    case RECONHECIDO = 'reconhecido';
    case NAO_RECONHECIDO = 'nao_reconhecido';
    case FINALIZADO = 'finalizado';

    public function isReconhecido(): bool
    {
        return $this === self::RECONHECIDO;
    }

    public function isNaoReconhecido(): bool
    {
        return $this === self::NAO_RECONHECIDO;
    }

    public static function toSelectArray(): array
    {
        $array = [];
        foreach (self::cases() as $case) {
            $array[$case->value] = ucfirst(str_replace('_', ' ', $case->value));
        }
        return $array;
    }
}
