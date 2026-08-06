<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use App\Modules\AjudaHumanitaria\Models\PedidoAh;
use App\Modules\AjudaHumanitaria\Support\MunicipioDoUsuario;
use App\Modules\Compdec\Enums\TipoOrgao;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Escopo do processo MAH.
 *
 * A permissao responde o que o usuario pode fazer; esta policy responde sobre
 * qual registro. As duas se somam: a rota exige o slug, a policy exige o
 * escopo.
 *
 * RN-24: quem tem municipio vinculado so enxerga o proprio. Quem nao tem opera
 * em ambito estadual e enxerga tudo, que e o caso do CEDEC.
 * RN-20: o perfil REDEC nao acessa prestacao de contas.
 */
class PedidoAhPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('humanitaria.pedidos.view');
    }

    public function view(User $user, PedidoAh $pedido): bool
    {
        return $user->can('humanitaria.pedidos.view')
            && $this->noEscopo($user, $pedido);
    }

    public function create(User $user): bool
    {
        return $user->can('humanitaria.pedidos.create');
    }

    /**
     * Edicao exige, alem da permissao e do escopo, que o pedido ainda esteja
     * com o municipio. A mesma regra vale no PedidoAhService; aqui ela existe
     * para a interface nao oferecer o botao.
     */
    public function update(User $user, PedidoAh $pedido): bool
    {
        return $user->can('humanitaria.pedidos.edit')
            && $this->noEscopo($user, $pedido)
            && $pedido->status === StatusPedidoAh::EdicaoCompdec;
    }

    public function delete(User $user, PedidoAh $pedido): bool
    {
        return $user->can('humanitaria.pedidos.delete')
            && $this->noEscopo($user, $pedido)
            && $pedido->status === StatusPedidoAh::EdicaoCompdec;
    }

    public function tramitar(User $user, PedidoAh $pedido): bool
    {
        return $user->can('humanitaria.pedidos.tramitar')
            && $this->noEscopo($user, $pedido);
    }

    public function parecer(User $user, PedidoAh $pedido): bool
    {
        return $user->can('humanitaria.pedidos.parecer')
            && $this->noEscopo($user, $pedido);
    }

    public function liberarItens(User $user, PedidoAh $pedido): bool
    {
        return $user->can('humanitaria.pedidos.liberar_itens')
            && $this->noEscopo($user, $pedido);
    }

    public function anexos(User $user, PedidoAh $pedido): bool
    {
        return $user->can('humanitaria.pedidos.anexos')
            && $this->noEscopo($user, $pedido);
    }

    /** RN-20. */
    public function verPrestacao(User $user, PedidoAh $pedido): bool
    {
        if ($this->ehPerfilRegional($user)) {
            return false;
        }

        return $user->can('humanitaria.prestacao.view')
            && $this->noEscopo($user, $pedido);
    }

    public function lancarEntrega(User $user, PedidoAh $pedido): bool
    {
        return $this->verPrestacao($user, $pedido)
            && $user->can('humanitaria.prestacao.lancar');
    }

    public function homologar(User $user, PedidoAh $pedido): bool
    {
        return $user->can('humanitaria.prestacao.homologar')
            && $this->noEscopo($user, $pedido);
    }

    /**
     * RN-24. Usuario sem municipio vinculado opera em ambito estadual.
     */
    private function noEscopo(User $user, PedidoAh $pedido): bool
    {
        $municipioDoUsuario = MunicipioDoUsuario::resolver($user);

        if ($municipioDoUsuario === null) {
            return true;
        }

        return $municipioDoUsuario === (int) $pedido->municipio_id;
    }

    /**
     * RN-20 e RN-23. O NewSDC nao modela a REDEC como cargo: o perfil regional
     * e o do usuario lotado em orgao de tipo REDEC, na hierarquia CEDEC, REDEC
     * e COMPDEC de TipoOrgao. A checagem por cargo fica pelo slug estavel para
     * o caso de o cargo vir a existir em config/permissions.php.
     */
    private function ehPerfilRegional(User $user): bool
    {
        if ($user->hasRole('redec')) {
            return true;
        }

        return MunicipioDoUsuario::orgaoDe($user)?->tipo === TipoOrgao::REDEC;
    }
}
