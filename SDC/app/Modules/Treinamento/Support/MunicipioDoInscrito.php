<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Support;

use App\Models\User;
use App\Modules\Compdec\Models\Orgao;
use App\Modules\Treinamento\Models\Cidadao;
use Illuminate\Database\Eloquent\Model;

/**
 * Municipio da PESSOA inscrita: servidor pelo orgao principal (banco sdc),
 * cidadao pelo cadastro. Uma consulta por resolucao, nunca JOIN entre bancos.
 */
final class MunicipioDoInscrito
{
    public static function de(Model $inscrito): ?int
    {
        if ($inscrito instanceof Cidadao) {
            return $inscrito->municipio_id !== null ? (int) $inscrito->municipio_id : null;
        }

        if ($inscrito instanceof User) {
            return self::doOrgao($inscrito->orgao_principal_id);
        }

        return null;
    }

    public static function doOrgao(int|string|null $orgaoId): ?int
    {
        if ($orgaoId === null) {
            return null;
        }

        $municipioId = Orgao::query()->whereKey($orgaoId)->value('municipio_id');

        return $municipioId !== null ? (int) $municipioId : null;
    }
}
