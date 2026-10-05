<?php

declare(strict_types=1);

namespace App\Modules\Pae\Support;

use Carbon\CarbonImmutable;

/**
 * Vigencia do CCPAE: o PAE aprovado e atualizado a cada 3 anos (Resolucao GMG 83/2024).
 *   - empreendimento novo: da publicacao da Licenca de Operacao (Art. 4);
 *   - empreendimento ja com LO: da emissao do CCPAE (Art. 5).
 *
 * addYearsNoOverflow: LO em 29/02 vence em 28/02, nao em 01/03.
 */
final class VigenciaCcpae
{
    public const ANOS = 3;

    public static function vencimento(mixed $dtEmissao, bool $empreendimentoNovo, mixed $dtLicencaOperacao): CarbonImmutable
    {
        $base = $empreendimentoNovo ? Datas::dia($dtLicencaOperacao) : Datas::dia($dtEmissao);

        if ($base === null) {
            throw new \InvalidArgumentException($empreendimentoNovo
                ? 'Empreendimento novo exige a data da Licenca de Operacao (Art. 4).'
                : 'CCPAE sem data de emissao.');
        }

        return $base->addYearsNoOverflow(self::ANOS);
    }
}
