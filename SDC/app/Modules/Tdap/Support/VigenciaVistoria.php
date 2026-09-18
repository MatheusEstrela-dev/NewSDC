<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Support;

use Carbon\Carbon;

/**
 * Fonte unica de verdade da vigencia de uma Vistoria de caminhao-pipa.
 *
 * REGRA DE NEGOCIO:
 *   valido_ate = data + VIGENCIA_MESES          (ultimo dia coberto, inclusive)
 *   cobre(X)   = valido_ate >= X                (a vistoria vale NA data X)
 *   vigente    = cobre(hoje)
 *   dias_restantes = valido_ate - hoje  (assinado: negativo = vencida, 0 = vence hoje)
 *
 * MOTIVO DE EXISTIR: a regra estava escrita em QUATRO pontos que nao
 * concordavam entre si -- Vistoria::getEstaVigenteAttribute (comparava com
 * `now()`, que traz a hora corrente), Vistoria::scopeVigente (whereDate, que
 * compara so o dia), Caminhao::vistoriaVigente (whereDate inline, duplicando o
 * scope) e FrotaService::obterEstatisticasDaFrota (SQL cru). O accessor e o
 * scope divergiam por UM DIA na borda exata dos 12 meses, e era o scope que o
 * guard de ativacao do cronograma usava: a tela dizia "vencida" e a ativacao
 * passava. Ver o teste de borda em VistoriaLoteFluxoTest.
 *
 * Toda comparacao aqui e por DIA (startOfDay), nunca por instante -- `data` e
 * coluna DATE e `now()` traz a hora corrente; misturar os dois foi a origem do
 * bug acima.
 *
 * DIVERGENCIA DELIBERADA em relacao a VigenciaAta: la, data ausente NAO torna a
 * ata vencida (sem data nao existe prazo a expirar). Aqui e o oposto -- vistoria
 * sem `data` NAO esta vigente. Uma ata sem prazo continua autorizando; uma
 * vistoria sem data nao prova que o caminhao foi inspecionado, e a ausencia de
 * prova nao pode liberar um veiculo para rodar.
 *
 * O parecer (aprovada/reprovada) NAO entra aqui: esta classe responde apenas
 * sobre datas. Quem chama combina as duas condicoes -- ver Vistoria e Caminhao,
 * onde o filtro de parecer precisa virar clausula SQL de qualquer forma.
 *
 * `$hoje` e injetavel para permitir teste puro, sem congelar o relogio global.
 *
 * USADO POR: Vistoria (accessors/scopes), Caminhao (vistoriaVigente),
 * FrotaService, CaminhaoIndexResource, CronogramaService (guard de ativacao) e
 * CronoCaminhaoResource.
 */
final class VigenciaVistoria
{
    /** Prazo de validade de uma vistoria aprovada, em meses. */
    public const VIGENCIA_MESES = 12;

    /**
     * Ultimo dia coberto pela vistoria (inclusive).
     *
     * addMonthsNoOverflow, e nao addMonths: vistoria de 29/02/2024 + 12 meses
     * cai num 29/02/2025 que nao existe, e o `addMonths` do Carbon transborda
     * para 01/03 -- 366 dias de validade em vez de 365. O codigo antigo tinha
     * exatamente essa inconsistencia: o accessor esta_vigente (que subtraia 12
     * meses de hoje) dizia VENCIDA em 01/03/2025, enquanto valido_ate (que
     * somava 12 meses a data) exibia 01/03/2025 na tela. NoOverflow fecha o dia
     * em 28/02 e faz as duas pontas concordarem.
     *
     * @param  mixed  $data  Carbon|string|null (coluna `data`)
     */
    public static function validoAte(mixed $data): ?Carbon
    {
        return self::toDate($data)?->addMonthsNoOverflow(self::VIGENCIA_MESES);
    }

    /**
     * A vistoria cobre a data de referencia?
     *
     * E a pergunta que o guard de ativacao do cronograma precisa fazer: nao
     * basta a vistoria estar vigente HOJE, ela tem que cobrir o fim do
     * cronograma que esta sendo ativado.
     *
     * @param  mixed  $data        Carbon|string|null (coluna `data`)
     * @param  mixed  $referencia  Carbon|string|null (a data que precisa estar coberta)
     */
    public static function cobre(mixed $data, mixed $referencia): bool
    {
        $validoAte = self::validoAte($data);
        $referencia = self::toDate($referencia);

        if ($validoAte === null || $referencia === null) {
            return false;
        }

        return $validoAte->greaterThanOrEqualTo($referencia);
    }

    /** A vistoria esta vigente hoje (ou na data injetada). */
    public static function estaVigente(mixed $data, ?Carbon $hoje = null): bool
    {
        return self::cobre($data, self::hoje($hoje));
    }

    /**
     * Menor `data` que ainda esta vigente na data de referencia.
     *
     * Existe para o SQL: scopeVigente e Caminhao::vistoriaVigente filtram com
     * `whereDate('data', '>=', ...)` e precisam da MESMA borda que o accessor,
     * senao voltamos ao bug de um dia que originou esta classe.
     *
     * `hoje - 12 meses` quase sempre e essa borda, mas NAO no dia 29/02:
     * `subMonthsNoOverflow` fecha 29/02/2024 em 28/02/2023, e
     * `validoAte(28/02/2023)` e 28/02/2024 -- um dia ANTES de hoje. O SQL
     * aceitava uma vistoria que o accessor ja dava por vencida, e era o SQL que
     * o guard de ativacao do cronograma consultava: a mesma classe de bug que
     * esta classe nasceu para matar, sobrevivendo num dia a cada quatro anos.
     *
     * Por isso a borda e verificada contra o proprio `cobre()` e empurrada ate
     * ficar coberta. O laco anda no maximo um dia (a aritmetica NoOverflow so
     * erra o arredondamento de fim de mes) e o resultado continua sendo uma
     * data fixa, do lado esquerdo de um `>=` -- o indice segue servindo.
     */
    public static function dataLimite(?Carbon $hoje = null): Carbon
    {
        $hoje = self::hoje($hoje);
        $limite = $hoje->copy()->subMonthsNoOverflow(self::VIGENCIA_MESES);

        while (! self::cobre($limite, $hoje)) {
            $limite->addDay();
        }

        return $limite;
    }

    /**
     * Dias ate o fim da vigencia, assinado.
     *
     * @return int|null Negativo = vencida, 0 = vence hoje, null = sem `data`
     */
    public static function diasRestantes(mixed $data, ?Carbon $hoje = null): ?int
    {
        $validoAte = self::validoAte($data);

        if ($validoAte === null) {
            return null;
        }

        return (int) self::hoje($hoje)->diffInDays($validoAte, false);
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
