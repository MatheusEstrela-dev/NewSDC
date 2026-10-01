<?php

declare(strict_types=1);

namespace App\Modules\Resgate\Support;

use App\Models\User;
use App\Modules\Resgate\Enums\EscopoCarteira;

/**
 * Ente de resgate do usuario: o municipio ou o orgao do seu orgao principal.
 *
 * Quem nao tem visao estadual ve SO o proprio ente; um id vindo da requisicao
 * so e aceito com `resgate.carteira.estado`. Isso impede espiar a carteira de
 * outro municipio trocando o parametro da URL.
 */
final class EnteDoUsuario
{
    public function resolver(User $user, EscopoCarteira $escopo, ?int $pedido): ?int
    {
        if ($pedido !== null && $user->can('resgate.carteira.estado')) {
            return $pedido;
        }

        $orgao = $user->orgaoPrincipal;
        $proprio = $escopo === EscopoCarteira::Municipio ? $orgao?->municipio_id : $orgao?->id;

        return $proprio !== null ? (int) $proprio : null;
    }
}
