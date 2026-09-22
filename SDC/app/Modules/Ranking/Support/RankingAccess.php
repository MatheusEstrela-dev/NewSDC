<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Support;

use App\Models\User;

final class RankingAccess
{
    public static function preview(?User $user): bool
    {
        return $user !== null && config('ranking.preview', false)
            && ($user->can('ranking.regras.view') || $user->can('is-admin'));
    }

    public static function visivel(?User $user): bool
    {
        return self::preview($user) || ($user !== null && config('ranking.habilitado')
            && ! config('ranking.modo_sombra') && $user->can('ranking.placar.view'));
    }
}
