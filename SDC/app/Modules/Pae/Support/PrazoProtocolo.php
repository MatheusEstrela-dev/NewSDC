<?php

declare(strict_types=1);

namespace App\Modules\Pae\Support;

use App\Support\Calendario\CalendarioDiasUteis;
use Carbon\CarbonImmutable;

/**
 * Prazo do empreendedor para protocolar a 2a secao do PAE na CEDEC: 10 dias
 * uteis contados da notificacao da FEAM (Resolucao GMG 83/2024, Art. 7, caput e §2).
 *
 * So gera aviso ("protocolado fora do prazo"); nao bloqueia a entrada.
 */
final class PrazoProtocolo
{
    public const DIAS_UTEIS = 10;

    public static function limite(mixed $dtNotificacaoFeam, CalendarioDiasUteis $calendario): ?CarbonImmutable
    {
        $feam = Datas::dia($dtNotificacaoFeam);

        return $feam === null ? null : $calendario->adicionarDiasUteis($feam, self::DIAS_UTEIS);
    }

    public static function foraDoPrazo(mixed $dtNotificacaoFeam, mixed $dtEntrada, CalendarioDiasUteis $calendario): bool
    {
        $limite = self::limite($dtNotificacaoFeam, $calendario);
        $entrada = Datas::dia($dtEntrada);

        return $limite !== null && $entrada !== null && $entrada->greaterThan($limite);
    }
}
