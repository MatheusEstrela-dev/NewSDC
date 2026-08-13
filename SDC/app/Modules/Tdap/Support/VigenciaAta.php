<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Support;

use App\Modules\Tdap\Enums\SituacaoAta;
use Carbon\Carbon;

/**
 * Fonte unica de verdade da vigencia de uma Ata de Registro de Precos.
 *
 * REGRA DE NEGOCIO (precedencia de cima para baixo):
 *   inativa   = ativo = false                  (desligada na mao vence tudo)
 *   agendada  = dt_inicio > hoje               (ainda nao comecou)
 *   vencida   = dt_final  < hoje               (passou da vigencia)
 *   vigente   = dt_inicio <= hoje <= dt_final  (inclui os dois extremos)
 *
 *   dias_restantes = dt_final - hoje  (assinado: negativo = vencida, 0 = vence hoje)
 *   proximo_vencer = vigente e dias_restantes <= JANELA_PROXIMO_VENCER_DIAS
 *
 * MOTIVO DE EXISTIR: antes desta classe a regra de vigencia estava duplicada em
 * quatro pontos (Ata::scopeVigente, AtaIndexResource, AtaResource e
 * AtaService::exportar) e nenhum deles distinguia uma ata VENCIDA de uma ata
 * ATIVA — uma ata ligada com dt_final no passado aparecia como "Ativa" na tela.
 *
 * Datas ausentes NAO tornam a ata vencida: sem data nao existe prazo a expirar
 * (mesma escolha de Decretacoes\Support\Vigencia), entao a ata segue vigente.
 *
 * `$hoje` e injetavel para permitir teste puro, sem congelar o relogio global.
 *
 * USADO POR: Ata (accessors/scopes), AtaIndexResource, AtaResource e AtaService.
 */
final class VigenciaAta
{
    /** Janela (dias) que classifica uma ata vigente como "proxima do vencimento". */
    public const JANELA_PROXIMO_VENCER_DIAS = 30;

    /**
     * Classifica a ata em um dos quatro estados de SituacaoAta.
     *
     * @param bool  $ativo  Flag manual da ata (coluna `ativo`)
     * @param mixed $inicio Carbon|string|null (dt_inicio)
     * @param mixed $final  Carbon|string|null (dt_final)
     */
    public static function situacao(
        bool $ativo,
        mixed $inicio,
        mixed $final,
        ?Carbon $hoje = null,
    ): SituacaoAta {
        // `ativo` tem precedencia absoluta: uma ata desligada nao volta a
        // vigorar so porque as datas ainda cobrem hoje.
        if (! $ativo) {
            return SituacaoAta::Inativa;
        }

        $hoje = self::hoje($hoje);
        $inicio = self::toDate($inicio);
        $final = self::toDate($final);

        if ($inicio !== null && $inicio->greaterThan($hoje)) {
            return SituacaoAta::Agendada;
        }

        if ($final !== null && $final->lessThan($hoje)) {
            return SituacaoAta::Vencida;
        }

        return SituacaoAta::Vigente;
    }

    /**
     * Dias ate o fim da vigencia, assinado.
     *
     * @param  mixed  $final  Carbon|string|null (dt_final)
     * @return int|null Negativo = vencida, 0 = vence hoje, null = sem dt_final
     */
    public static function diasRestantes(mixed $final, ?Carbon $hoje = null): ?int
    {
        $final = self::toDate($final);

        if ($final === null) {
            return null;
        }

        return (int) self::hoje($hoje)->diffInDays($final, false);
    }

    /**
     * Ata vigente que expira dentro da janela de alerta (30 dias).
     *
     * Só alerta sobre ata VIGENTE: agendada, vencida e inativa nao geram aviso
     * de "vence em X dias" (a vencida ja venceu, a agendada nem comecou).
     */
    public static function isProximaVencer(
        bool $ativo,
        mixed $inicio,
        mixed $final,
        ?Carbon $hoje = null,
    ): bool {
        if (! self::situacao($ativo, $inicio, $final, $hoje)->isVigente()) {
            return false;
        }

        $dias = self::diasRestantes($final, $hoje);

        return $dias !== null && $dias >= 0 && $dias <= self::JANELA_PROXIMO_VENCER_DIAS;
    }

    /** Normaliza a referencia de "hoje" para a meia-noite (comparacao por dia). */
    private static function hoje(?Carbon $hoje): Carbon
    {
        return ($hoje ?? Carbon::now())->copy()->startOfDay();
    }

    /** Converte um valor cru de data em Carbon a meia-noite. */
    private static function toDate(mixed $valor): ?Carbon
    {
        if ($valor instanceof Carbon) {
            return $valor->copy()->startOfDay();
        }

        if ($valor === null || $valor === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $valor)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
