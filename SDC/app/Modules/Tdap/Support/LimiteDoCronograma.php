<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Support;

use App\Modules\Tdap\Models\Cronograma;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Limite operacional do cronograma: o ultimo dia coberto, ja com prorrogacao.
 *
 * Uma so definicao para registro de viagem, decisao do COMPDEC e sinalizacao
 * na tela -- com tres copias da comparacao, uma tela diria "dentro do prazo" e
 * o servidor recusaria.
 *
 * Compara por DIA: uma viagem as 23:59 do ultimo dia ainda esta dentro.
 * Cronograma sem data final nao tem limite.
 */
final class LimiteDoCronograma
{
    public static function limite(?Cronograma $cronograma): ?Carbon
    {
        $fim = $cronograma?->dt_final_efetiva;

        return $fim !== null ? Carbon::instance($fim)->startOfDay() : null;
    }

    public static function ultrapassa(?Cronograma $cronograma, CarbonInterface|string|null $dia): bool
    {
        $limite = self::limite($cronograma);

        if ($limite === null || $dia === null) {
            return false;
        }

        return Carbon::parse($dia)->startOfDay()->greaterThan($limite);
    }

    /**
     * O municipio decide (confirma ou reprova) toda viagem que caiba no
     * cronograma: o que conta e a DATA DA VIAGEM, nao o dia da decisao.
     *
     * Antes tambem se exigia "hoje ate o limite". Com a confirmacao do
     * municipio obrigatoria para a CEDEC aprovar, isso travava para sempre a
     * viagem feita no prazo e atestada depois do fim -- e a agua entregue nao
     * era paga (achado A1 da auditoria de 2026-11-05).
     */
    public static function permiteDecisao(?Cronograma $cronograma, CarbonInterface|string|null $dataDaViagem): bool
    {
        return ! self::ultrapassa($cronograma, $dataDaViagem);
    }
}
