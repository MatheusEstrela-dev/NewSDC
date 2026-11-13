<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Support;

use App\Modules\Treinamento\Models\Video;
use App\Modules\Treinamento\Services\ProfessorService;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Regra de quem gerencia um video da biblioteca: o dono ou quem edita cursos.
 */
class PermissaoDeVideo
{
    public function podeGerenciar(Video $video, ?Authenticatable $user): bool
    {
        return $user !== null
            && ((int) $video->user_id === (int) $user->getAuthIdentifier() || $user->can(ProfessorService::PERMISSAO_ADMIN));
    }
}
