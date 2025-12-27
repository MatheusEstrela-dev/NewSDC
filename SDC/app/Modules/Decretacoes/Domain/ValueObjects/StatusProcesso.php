<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Domain\ValueObjects;

/**
 * Value Object: Status do Processo de Reconhecimento
 */
enum StatusProcesso: string
{
    case REGISTRO = 'Registro';
    case AGUARDANDO_ASSINATURA_CEDEC = 'Aguardando assinatura clube CEDEC';
    case AGUARDANDO_ASSINATURA_DIRETOR = 'Aguardando assinatura Diretor da DAT';
    case AGUARDANDO_ANALISE_ESTADO = 'Aguardando Análise do Estado';
    case EM_ANALISE_ESTADO = 'Em análise pelo Estado';
    case AGUARDANDO_AJUSTES_MUNICIPIO = 'Aguardando ajustes do município';
    case AGUARDANDO_NOTA_JURIDICA = 'Aguardando Nota Jurídica';
    case RECONHECIDO_ESTADO_AGUARDANDO_UNIAO = 'Reconhecido pelo Estado / Aguardando análise da União';
    case RECONHECIDO_ESTADO_E_UNIAO = 'Reconhecido pelo Estado e pela União';
    case ENVIADO_PUBLICACAO = 'Enviado para Publicação';
    case ENVIO_DIRETO_UNIAO = 'Envio Direto para União';
    case RECONHECIDO_SOMENTE_UNIAO = 'Reconhecido somente pela União';
    case RECONHECIDO_SOMENTE_ESTADO = 'Reconhecido somente pelo Estado';
    case NAO_RECONHECIDO_ESTADO = 'Não reconhecido pelo Estado';
    case NAO_RECONHECIDO_UNIAO = 'Não reconhecido pela União';
    case NAO_RECONHECIDO_ESTADO_UNIAO = 'Não reconhecido pelo Estado e União';

    /**
     * Verifica se o status indica reconhecimento
     */
    public function isReconhecido(): bool
    {
        return in_array($this, [
            self::RECONHECIDO_ESTADO_AGUARDANDO_UNIAO,
            self::RECONHECIDO_ESTADO_E_UNIAO,
            self::RECONHECIDO_SOMENTE_UNIAO,
            self::RECONHECIDO_SOMENTE_ESTADO,
        ]);
    }

    /**
     * Verifica se o status indica não reconhecimento
     */
    public function isNaoReconhecido(): bool
    {
        return in_array($this, [
            self::NAO_RECONHECIDO_ESTADO,
            self::NAO_RECONHECIDO_UNIAO,
            self::NAO_RECONHECIDO_ESTADO_UNIAO,
        ]);
    }

    /**
     * Verifica se o status está em análise
     */
    public function isEmAnalise(): bool
    {
        return in_array($this, [
            self::AGUARDANDO_ANALISE_ESTADO,
            self::EM_ANALISE_ESTADO,
            self::AGUARDANDO_NOTA_JURIDICA,
        ]);
    }

    /**
     * Retorna cor para badge no frontend
     */
    public function getBadgeColor(): string
    {
        return match($this) {
            self::REGISTRO => 'blue',
            self::AGUARDANDO_ASSINATURA_CEDEC,
            self::AGUARDANDO_ASSINATURA_DIRETOR,
            self::AGUARDANDO_ANALISE_ESTADO,
            self::EM_ANALISE_ESTADO,
            self::AGUARDANDO_NOTA_JURIDICA => 'yellow',
            self::AGUARDANDO_AJUSTES_MUNICIPIO => 'orange',
            self::RECONHECIDO_ESTADO_AGUARDANDO_UNIAO,
            self::RECONHECIDO_ESTADO_E_UNIAO,
            self::RECONHECIDO_SOMENTE_UNIAO,
            self::RECONHECIDO_SOMENTE_ESTADO => 'green',
            self::NAO_RECONHECIDO_ESTADO,
            self::NAO_RECONHECIDO_UNIAO,
            self::NAO_RECONHECIDO_ESTADO_UNIAO => 'red',
            default => 'gray',
        };
    }

    /**
     * Lista todos os status disponíveis para select
     */
    public static function toSelectArray(): array
    {
        return array_map(
            fn(self $status) => [
                'value' => $status->value,
                'label' => $status->value,
                'color' => $status->getBadgeColor(),
            ],
            self::cases()
        );
    }
}
