<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Support;

use Carbon\Carbon;

/**
 * Fonte unica de verdade do calculo de vigencia do decreto municipal.
 *
 * REGRA DE NEGOCIO:
 *   data_vencimento  = data_publicacao_mg + prazo_vigencia (dias)
 *   prazo_vigencia   = valor informado ou PRAZO_PADRAO_DIAS (180) quando ausente/invalido
 *   dias_restantes   = data_vencimento - hoje  (assinado: negativo = vencido; 0 = vence hoje)
 *   vigente          = dias_restantes >= 0  (sem data de publicacao => sem vigencia a expirar)
 *   proximo_vencer   = vigente e dias_restantes <= JANELA_PROXIMO_VENCER_DIAS
 *
 * Os 180 dias sao o prazo padrao de SE/ECP: registros legados sem
 * `prazo_vigencia` preenchido ficavam fora de "vigentes" E de "vencidos"
 * (NULL || ' days' => NULL no Postgres). O COALESCE de sqlVencimento() e o
 * fallback de prazo() corrigem isso em todos os pontos de calculo.
 *
 * USADO POR: Processo (accessors/scopes), ProcessoResource, ProcessoFilter,
 * ProcessoStatsService e o composable JS `useVigencia` (mesma regra no front).
 */
final class Vigencia
{
    /** Prazo padrao de vigencia (dias) quando o processo nao informa um. */
    public const PRAZO_PADRAO_DIAS = 180;

    /** Janela (dias) que classifica um decreto como "proximo ao vencimento". */
    public const JANELA_PROXIMO_VENCER_DIAS = 30;

    /** Prazos usuais oferecidos no formulario (atalhos). */
    public const PRAZOS_USUAIS = [30, 60, 90, 180, 365];

    /**
     * Normaliza o prazo de vigencia, aplicando o padrao de 180 dias.
     *
     * @param mixed $prazo Valor cru vindo do banco/request (int, string, null)
     * @return int Prazo efetivo em dias (>= 1)
     */
    public static function prazo(mixed $prazo): int
    {
        $dias = is_numeric($prazo) ? (int) $prazo : 0;

        return $dias > 0 ? $dias : self::PRAZO_PADRAO_DIAS;
    }

    /**
     * Indica se o prazo informado esta ausente e portanto assumiu o padrao.
     */
    public static function usouPrazoPadrao(mixed $prazo): bool
    {
        return !(is_numeric($prazo) && (int) $prazo > 0);
    }

    /**
     * Calcula a data de vencimento (publicacao + prazo efetivo).
     *
     * @param mixed $dataPublicacao Carbon|string|null (data_publicacao_mg)
     * @param mixed $prazo Prazo cru; 180 quando ausente
     * @return Carbon|null Vencimento (meia-noite) ou null sem data de publicacao
     */
    public static function vencimento(mixed $dataPublicacao, mixed $prazo): ?Carbon
    {
        $inicio = self::toDate($dataPublicacao);

        if (!$inicio) {
            return null;
        }

        return $inicio->copy()->addDays(self::prazo($prazo));
    }

    /**
     * Dias restantes ate o vencimento, assinado.
     *
     * @return int|null Negativo = vencido, 0 = vence hoje, null = sem vigencia
     */
    public static function diasRestantes(mixed $dataPublicacao, mixed $prazo, ?Carbon $hoje = null): ?int
    {
        $vencimento = self::vencimento($dataPublicacao, $prazo);

        if (!$vencimento) {
            return null;
        }

        $hoje = ($hoje ?? Carbon::today())->copy()->startOfDay();

        return (int) $hoje->diffInDays($vencimento->copy()->startOfDay(), false);
    }

    /**
     * Decreto ainda dentro do prazo (inclui o proprio dia do vencimento).
     * Sem data de publicacao nao existe prazo a expirar => vigente.
     */
    public static function isVigente(mixed $dataPublicacao, mixed $prazo, ?Carbon $hoje = null): bool
    {
        $dias = self::diasRestantes($dataPublicacao, $prazo, $hoje);

        return $dias === null || $dias >= 0;
    }

    /** Decreto vencido (passou da data de vencimento). */
    public static function isVencido(mixed $dataPublicacao, mixed $prazo, ?Carbon $hoje = null): bool
    {
        $dias = self::diasRestantes($dataPublicacao, $prazo, $hoje);

        return $dias !== null && $dias < 0;
    }

    /** Decreto vigente que vence dentro da janela de alerta (30 dias). */
    public static function isProximoVencer(mixed $dataPublicacao, mixed $prazo, ?Carbon $hoje = null): bool
    {
        $dias = self::diasRestantes($dataPublicacao, $prazo, $hoje);

        return $dias !== null && $dias >= 0 && $dias <= self::JANELA_PROXIMO_VENCER_DIAS;
    }

    /**
     * Expressao SQL (Postgres) da data de vencimento, com o padrao de 180 dias.
     *
     * @param string $colPublicacao Coluna da data de publicacao
     * @param string $colPrazo Coluna do prazo em dias
     * @return string Expressao que resulta em `date`
     */
    public static function sqlVencimento(
        string $colPublicacao = 'data_publicacao_mg',
        string $colPrazo = 'prazo_vigencia'
    ): string {
        $padrao = self::PRAZO_PADRAO_DIAS;

        return "({$colPublicacao} + (COALESCE({$colPrazo}, {$padrao}) || ' days')::interval)::date";
    }

    /**
     * Converte um valor cru de data em Carbon a meia-noite.
     */
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
