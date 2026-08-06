<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Modules\Treinamento\Models\Certificado;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Policy de Certificado de Treinamento.
 *
 * Não estende BasePolicy: essa Policy precisa autorizar tanto um
 * App\Models\User (staff, guard "web") quanto um
 * App\Modules\Treinamento\Models\Cidadao (guard "cidadao"), e
 * BasePolicy::before() tem type-hint fechado em User — receber um
 * Cidadao ali quebraria com TypeError antes de chegar aqui.
 */
class CertificadoPolicy
{
    use HandlesAuthorization;

    /**
     * Dono da inscrição (via inscrito_type/inscrito_id) ou staff com a
     * permissão de download de qualquer certificado.
     */
    public function view(Authenticatable $principal, Certificado $certificado): bool
    {
        $certificado->loadMissing('inscricao');

        $ehDono = $certificado->inscricao->inscrito_type === $principal::class
            && $certificado->inscricao->inscrito_id === $principal->getAuthIdentifier();

        if ($ehDono) {
            return true;
        }

        return $principal instanceof User && $principal->can('treinamento.certificados.download');
    }
}
