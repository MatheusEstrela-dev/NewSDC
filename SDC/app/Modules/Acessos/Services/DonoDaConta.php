<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Services;

use App\Models\User;
use App\Modules\Acessos\Models\CadastroAcesso;
use Illuminate\Database\Eloquent\Builder;

/**
 * Ninguem age sobre a propria conta (spec 7.2 item 3): o alvo e do ator se o
 * cadastro e dele (user_id ou CPF), se o login e o login_ad de algum cadastro
 * dele ou, no worker, se o objectGUID resolvido e o de algum cadastro dele.
 * CPF comparado so pelos digitos dos dois lados.
 */
final class DonoDaConta
{
    public function eDoAtor(User $ator, ?CadastroAcesso $cadastro, string $login, ?string $objectGuid = null): bool
    {
        $cpf = self::digitos($ator->cpf);

        if ($cadastro !== null && (
            ($cadastro->user_id !== null && (int) $cadastro->user_id === (int) $ator->getKey())
            || ($cpf !== '' && self::digitos($cadastro->cpf) === $cpf)
        )) {
            return true;
        }

        $login = mb_strtolower(trim($login));
        $objectGuid = $objectGuid === null ? '' : mb_strtolower(trim($objectGuid));
        if ($login === '' && $objectGuid === '') {
            return false;
        }

        return CadastroAcesso::query()
            ->where(function (Builder $query) use ($ator, $cpf): void {
                $query->where('user_id', $ator->getKey());
                if ($cpf !== '') {
                    $query->orWhereRaw("regexp_replace(cpf, '[^0-9]', '', 'g') = ?", [$cpf]);
                }
            })
            ->where(function (Builder $query) use ($login, $objectGuid): void {
                if ($login !== '') {
                    $query->orWhereRaw('lower(login_ad) = ?', [$login]);
                }
                if ($objectGuid !== '') {
                    $query->orWhereRaw('lower(object_guid::text) = ?', [$objectGuid]);
                }
            })
            ->exists();
    }

    private static function digitos(mixed $cpf): string
    {
        return (string) preg_replace('/\D/', '', (string) $cpf);
    }
}
