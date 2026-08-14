<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Support\PerfilCisterna;

class CisternaBeneficiarioPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('cisternas.beneficiarios.view');
    }

    public function view(User $user, CisternaBeneficiario $beneficiario): bool
    {
        if (! $user->can('cisternas.beneficiarios.view')) {
            return false;
        }

        return $this->dentroDoTerritorio($user, $beneficiario);
    }

    public function create(User $user): bool
    {
        return $user->can('cisternas.beneficiarios.create');
    }

    public function update(User $user, CisternaBeneficiario $beneficiario): bool
    {
        if (! $user->can('cisternas.beneficiarios.edit')) {
            return false;
        }

        return $this->dentroDoTerritorio($user, $beneficiario);
    }

    public function delete(User $user, CisternaBeneficiario $beneficiario): bool
    {
        return $user->can('cisternas.beneficiarios.delete')
            && $this->dentroDoTerritorio($user, $beneficiario);
    }

    public function export(User $user): bool
    {
        return $user->can('cisternas.beneficiarios.export');
    }

    /**
     * COMPDEC ve somente o proprio municipio. CEDEC, fornecedor e usuarios
     * sem orgao nao tem recorte territorial nesta camada — a listagem aplica
     * o recorte proprio de cada perfil no BeneficiarioService.
     */
    private function dentroDoTerritorio(User $user, CisternaBeneficiario $beneficiario): bool
    {
        $municipioId = PerfilCisterna::deUsuario($user)->municipioId();

        return $municipioId === null || $municipioId === (int) $beneficiario->municipio_id;
    }
}
