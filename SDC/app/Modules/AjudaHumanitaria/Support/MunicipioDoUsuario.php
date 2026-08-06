<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Support;

use App\Models\User;
use App\Modules\Compdec\Models\Orgao;

/**
 * Municipio ao qual o usuario esta vinculado, ou null quando ele opera em
 * ambito estadual.
 *
 * A cadeia de fallback reproduz a de PlanConController::store, unico lugar do
 * projeto que ja resolvia isso: orgao principal, depois o orgao marcado como
 * principal no pivot, depois o unico orgao vinculado.
 *
 * Null nao e erro: usuarios do CEDEC nao tem orgao municipal e enxergam o
 * estado inteiro. Quem trata a ausencia como impedimento e o fluxo de
 * abertura de pedido, nao a leitura.
 */
final class MunicipioDoUsuario
{
    public static function resolver(User $user): ?int
    {
        $municipioId = self::orgaoDe($user)?->municipio_id;

        return $municipioId !== null ? (int) $municipioId : null;
    }

    /**
     * Orgao de lotacao do usuario, pela mesma cadeia de fallback.
     *
     * Exposto porque a PedidoAhPolicy precisa do tipo do orgao, e nao apenas
     * do municipio, para reconhecer o perfil regional (RN-20). Manter a cadeia
     * em um unico lugar evita que ela se duplique na policy.
     */
    public static function orgaoDe(User $user): ?Orgao
    {
        $user->loadMissing('orgaoPrincipal');

        return $user->orgaoPrincipal
            ?? $user->orgaos()->wherePivot('is_principal', true)->first()
            ?? ($user->orgaos()->count() === 1 ? $user->orgaos()->first() : null);
    }
}
