<?php

namespace App\Listeners;

use App\Models\Role;
use App\Models\User;
use App\Services\Auth\SessionVersionService;
use Illuminate\Auth\Events\Login;
use Illuminate\Events\Dispatcher;

/**
 * Liga o carimbo de sessao (SessionVersionService) aos eventos do sistema:
 * carimba no login e sobe a versao quando cargo/permissao de um usuario muda.
 * (Mudancas de status, senha, e-mail e orgao ficam no UserObserver.)
 */
class SessionVersionSubscriber
{
    public function __construct(private readonly SessionVersionService $versions)
    {
    }

    public function aoEntrar(Login $event): void
    {
        $request = request();

        if ($event->user instanceof User && $request->hasSession()) {
            $this->versions->stampLogin($request->session(), $event->user);
        }
    }

    /**
     * Eventos RoleAttached/RoleDetached/PermissionAttached/PermissionDetached
     * do Spatie; $event->model e o usuario (ou a role, ao mexer nas permissoes
     * do cargo -- ai caem todos os usuarios dele).
     */
    public function aoMudarCargoOuPermissao(object $event): void
    {
        $model = $event->model ?? null;

        if ($model instanceof User) {
            $this->versions->bumpUser($model);
        } elseif ($model instanceof Role) {
            $this->versions->bump($model->users()->pluck('users.id')->all());
        }
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'aoEntrar',
            'Spatie\Permission\Events\RoleAttached' => 'aoMudarCargoOuPermissao',
            'Spatie\Permission\Events\RoleDetached' => 'aoMudarCargoOuPermissao',
            'Spatie\Permission\Events\PermissionAttached' => 'aoMudarCargoOuPermissao',
            'Spatie\Permission\Events\PermissionDetached' => 'aoMudarCargoOuPermissao',
        ];
    }
}
