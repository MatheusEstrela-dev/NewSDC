<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Enums;

enum StatusProcesso: string
{
    case REGISTRO                        = 'Registro';
    case AGUARDANDO_ASSINATURA_CEDEC     = 'Aguardando assinatura clube CEDEC';
    case AGUARDANDO_ASSINATURA_DAT       = 'Aguardando assinatura Diretor da DAT';
    case AGUARDANDO_ANALISE_ESTADO       = 'Aguardando Analise do Estado';
    case EM_ANALISE_ESTADO               = 'Em analise pelo Estado';
    case AGUARDANDO_AJUSTES_MUNICIPIO    = 'Aguardando ajustes do municipio';
    case AGUARDANDO_NOTA_JURIDICA        = 'Aguardando Nota Juridica';
    case RECONHECIDO_ESTADO_AG_UNIAO     = 'Reconhecido pelo Estado / Aguardando analise da Uniao';
    case RECONHECIDO_ESTADO_E_UNIAO      = 'Reconhecido pelo Estado e pela Uniao';
    case ENVIADO_PUBLICACAO              = 'Enviado para Publicacao';
    case ENVIO_DIRETO_UNIAO              = 'Envio Direto para Uniao';
    case RECONHECIDO_SOMENTE_UNIAO       = 'Reconhecido somente pela Uniao';
    case RECONHECIDO_SOMENTE_ESTADO      = 'Reconhecido somente pelo Estado';
    case NAO_RECONHECIDO_ESTADO          = 'Nao reconhecido pelo Estado';
    case NAO_RECONHECIDO_UNIAO           = 'Nao reconhecido pela Uniao';
    case NAO_RECONHECIDO_ESTADO_E_UNIAO  = 'Nao reconhecido pelo Estado e Uniao';

    public function isReconhecido(): bool
    {
        return in_array($this, [
            self::RECONHECIDO_ESTADO_E_UNIAO,
            self::RECONHECIDO_SOMENTE_ESTADO,
            self::RECONHECIDO_SOMENTE_UNIAO,
            self::RECONHECIDO_ESTADO_AG_UNIAO,
        ]);
    }

    public function isNaoReconhecido(): bool
    {
        return in_array($this, [
            self::NAO_RECONHECIDO_ESTADO,
            self::NAO_RECONHECIDO_UNIAO,
            self::NAO_RECONHECIDO_ESTADO_E_UNIAO,
        ]);
    }

    /**
     * Retorna array no formato [{value, label}] para o FormSelect Vue.
     */
    public static function toSelectOptions(): array
    {
        return array_map(
            fn (self $case) => ['value' => $case->value, 'label' => $case->value],
            self::cases()
        );
    }
}
