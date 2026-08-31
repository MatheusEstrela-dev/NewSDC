<?php

declare(strict_types=1);

namespace App\Support\Perfil;

use App\Models\User;
use App\Modules\Compdec\Models\Orgao;

/**
 * Orgao de lotacao do usuario.
 *
 * Um usuario pode estar ligado a varios orgaos, e nem sempre com
 * `orgao_principal_id` preenchido. A cadeia de fallback -- orgao principal,
 * depois o orgao marcado como principal no pivot, depois o unico orgao
 * vinculado -- nasceu em PlanConController::store e foi extraida para
 * AjudaHumanitaria\Support\MunicipioDoUsuario.
 *
 * Mora aqui, e nao dentro de um modulo, porque tres modulos ja dependem dela
 * (AjudaHumanitaria, PMDA e a PedidoAhPolicy) e importar entre modulos de
 * dominio para reusar uma regra transversal inverte a dependencia.
 *
 * Null nao e erro: usuarios do CEDEC operam em ambito estadual e nao tem orgao
 * municipal. Quem trata a ausencia como impedimento e cada fluxo de escrita,
 * nao a leitura.
 */
final class OrgaoDeLotacao
{
    public static function resolver(User $user): ?Orgao
    {
        $user->loadMissing('orgaoPrincipal');

        return $user->orgaoPrincipal
            ?? $user->orgaos()->wherePivot('is_principal', true)->first()
            ?? ($user->orgaos()->count() === 1 ? $user->orgaos()->first() : null);
    }

    /** Municipio do orgao de lotacao, ou null quando o usuario e estadual. */
    public static function municipioId(User $user): ?int
    {
        $municipioId = self::resolver($user)?->municipio_id;

        return $municipioId !== null ? (int) $municipioId : null;
    }
}
