<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Modules\Cisterna\Models\CisternaVistoria;
use App\Modules\Cisterna\Support\PerfilCisterna;

class CisternaVistoriaPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('cisternas.vistorias.view');
    }

    public function view(User $user, CisternaVistoria $vistoria): bool
    {
        return $user->can('cisternas.vistorias.view')
            && $this->dentroDoTerritorio($user, $vistoria);
    }

    public function create(User $user): bool
    {
        return $user->can('cisternas.vistorias.create');
    }

    public function update(User $user, CisternaVistoria $vistoria): bool
    {
        return $user->can('cisternas.vistorias.edit')
            && $this->dentroDoTerritorio($user, $vistoria);
    }

    public function delete(User $user, CisternaVistoria $vistoria): bool
    {
        return $user->can('cisternas.vistorias.delete')
            && $this->dentroDoTerritorio($user, $vistoria);
    }

    private function dentroDoTerritorio(User $user, CisternaVistoria $vistoria): bool
    {
        $municipioId = PerfilCisterna::deUsuario($user)->municipioId();

        if ($municipioId === null) {
            return true;
        }

        return $municipioId === (int) $vistoria->beneficiario?->municipio_id;
    }
}
