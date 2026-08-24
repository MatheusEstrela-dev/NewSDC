<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Support;

use App\Models\User;
use App\Modules\Compdec\Models\Orgao;
use App\Support\Perfil\OrgaoDeLotacao;

/**
 * Municipio ao qual o usuario esta vinculado, ou null quando ele opera em
 * ambito estadual.
 *
 * A cadeia de fallback vive em App\Support\Perfil\OrgaoDeLotacao desde que o
 * PMDA passou a precisar da mesma regra. Esta classe permanece como o nome que
 * o dominio de Ajuda Humanitaria usa (RN-24) e apenas delega.
 *
 * Null nao e erro: usuarios do CEDEC nao tem orgao municipal e enxergam o
 * estado inteiro. Quem trata a ausencia como impedimento e o fluxo de
 * abertura de pedido, nao a leitura.
 */
final class MunicipioDoUsuario
{
    public static function resolver(User $user): ?int
    {
        return OrgaoDeLotacao::municipioId($user);
    }

    /**
     * Orgao de lotacao do usuario, pela mesma cadeia de fallback.
     *
     * Exposto porque a PedidoAhPolicy precisa do tipo do orgao, e nao apenas
     * do municipio, para reconhecer o perfil regional (RN-20).
     */
    public static function orgaoDe(User $user): ?Orgao
    {
        return OrgaoDeLotacao::resolver($user);
    }
}
