<?php

declare(strict_types=1);

namespace App\Modules\Resgate\Support;

use App\Models\User;
use App\Modules\Resgate\Enums\EscopoCarteira;

/**
 * Quem enxerga e quem age pelo MUNICIPIO num pedido. Um lugar so para pagina,
 * anexos e acoes da execucao.
 *
 *  - ve: quem decide/entrega (CEDEC), quem tem visao estadual, ou o proprio
 *        ente do pedido (resgate.carteira.view);
 *  - age pelo ente: resgate.solicitar E o pedido e do ente do usuario.
 */
final class VisaoDoPedido
{
    public function __construct(private readonly EnteDoUsuario $entes) {}

    /** @param array<string, mixed> $pedido */
    public function podeVer(?User $user, array $pedido): bool
    {
        if ($user === null) {
            return false;
        }
        if ($user->can('resgate.aprovar') || $user->can('resgate.entregar') || $user->can('resgate.carteira.estado')) {
            return true;
        }

        return $user->can('resgate.carteira.view') && $this->doProprioEnte($user, $pedido);
    }

    /** @param array<string, mixed> $pedido */
    public function agePeloEnte(?User $user, array $pedido): bool
    {
        return $user !== null && $user->can('resgate.solicitar') && $this->doProprioEnte($user, $pedido);
    }

    /** @param array<string, mixed> $pedido */
    private function doProprioEnte(User $user, array $pedido): bool
    {
        // Sem id pedido: resolve SEMPRE pelo vinculo do usuario.
        $proprio = $this->entes->resolver($user, EscopoCarteira::from($pedido['ente_escopo']), null);

        return $proprio !== null && $proprio === (int) $pedido['ente_id'];
    }
}
