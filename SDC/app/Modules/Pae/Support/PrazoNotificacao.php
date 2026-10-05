<?php

declare(strict_types=1);

namespace App\Modules\Pae\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Prazo da diligencia: 30 dias da emissao da notificacao (Resolucao GMG 83/2024,
 * Art. 11), estendidos pelas dilacoes aprovadas pela CEDEC.
 *
 * Fonte unica dos "30 dias": antes estavam escritos em quatro pontos do
 * PaeNotificacaoService e na PaeNotificacaoResource.
 */
final class PrazoNotificacao
{
    public const PRAZO_DIAS = 30;

    public static function vencimento(mixed $dtNotificacao, int $diasDilacao = 0): CarbonImmutable
    {
        $emissao = Datas::dia($dtNotificacao)
            ?? throw new \InvalidArgumentException('Notificacao sem data de emissao.');

        return $emissao->addDays(self::PRAZO_DIAS + max(0, $diasDilacao));
    }

    public static function vencida(
        mixed $dtNotificacao,
        int $diasDilacao,
        mixed $dtDevolutiva,
        ?CarbonInterface $hoje = null,
    ): bool {
        if (Datas::dia($dtDevolutiva) !== null) {
            return false;
        }

        return self::vencimento($dtNotificacao, $diasDilacao)->lessThan(Datas::hoje($hoje));
    }
}
