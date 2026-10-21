<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Support;

use App\Models\User;
use App\Support\Perfil\OrgaoDeLotacao;

/**
 * Sobre que municipio(s) a pessoa LE os dados do TDAP: dashboard e fila de
 * validacao. Null = estado inteiro.
 *
 * Regra: quem administra o modulo (`tdap.admin`) le o estado inteiro mesmo
 * lotado num COMPDEC -- o Admin Geral do dev e lotado no COMPDEC Porteirinha
 * para testar o fluxo do municipio, e com o recorte puro de lotacao o
 * dashboard dele zerava. Os demais seguem OrgaoDeLotacao: lotado em municipio
 * le so o municipio; sem municipio (CEDEC) le tudo.
 *
 * NAO vale para a decisao do COMPDEC (confirmar/reprovar): ali o municipio e
 * de quem decide, e o fluxo continua lendo OrgaoDeLotacao direto.
 */
final class EscopoDeLeitura
{
    public static function municipioId(?User $user): ?int
    {
        if ($user === null || $user->can('tdap.admin')) {
            return null;
        }

        return OrgaoDeLotacao::municipioId($user);
    }

    /**
     * 403 quando o registro e de municipio fora do escopo de quem pede.
     *
     * Para acesso por id: a listagem ja vem recortada, mas a URL de uma ficha
     * pode ser digitada.
     */
    public static function garantir(?User $user, ?int $municipioId): void
    {
        $escopo = self::municipioId($user);

        abort_if($escopo !== null && $escopo !== (int) $municipioId, 403);
    }
}
