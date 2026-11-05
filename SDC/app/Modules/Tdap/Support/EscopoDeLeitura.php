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
 * dashboard dele zerava. Os demais seguem OrgaoDeLotacao:
 *  - lotado em orgao de municipio   -> le so o municipio;
 *  - lotado em orgao sem municipio  -> estadual (CEDEC), le tudo;
 *  - SEM lotacao resolvida          -> nao le nada (SEM_ESCOPO).
 *
 * Antes "sem lotacao" caia no mesmo caso do estadual: usuario municipal com
 * cadastro incompleto via e alterava cronograma de qualquer municipio
 * (auditoria de 2026-11-05). Estadual agora e quem esta lotado num orgao
 * estadual, nao quem nao tem orgao.
 *
 * NAO vale para a decisao do COMPDEC (confirmar/reprovar): ali o municipio e
 * de quem decide, e o fluxo continua lendo OrgaoDeLotacao direto.
 */
final class EscopoDeLeitura
{
    /**
     * Municipio inexistente: todo chamador filtra `municipio_id = escopo`
     * quando o escopo nao e null, entao este valor devolve listas vazias e
     * 403 em garantir() sem cada tela precisar tratar o caso.
     */
    public const SEM_ESCOPO = 0;

    public static function municipioId(?User $user): ?int
    {
        if ($user === null || $user->can('tdap.admin')) {
            return null;
        }

        $orgao = OrgaoDeLotacao::resolver($user);

        if ($orgao === null) {
            return self::SEM_ESCOPO;
        }

        return $orgao->municipio_id !== null ? (int) $orgao->municipio_id : null;
    }

    /** Estadual (CEDEC) ou administrador do modulo: le e escreve o estado inteiro. */
    public static function ehEstadual(?User $user): bool
    {
        return $user !== null && self::municipioId($user) === null;
    }

    /**
     * 403 para quem nao e estadual. Cadastros compartilhados entre municipios
     * (frota, vistorias, prestadores, atas, lotes) nao tem municipio unico:
     * so o estado altera.
     */
    public static function garantirEstadual(?User $user): void
    {
        abort_unless(self::ehEstadual($user), 403, 'Somente usuarios estaduais podem alterar este cadastro.');
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
