<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/*
 * Canal do pipeline medalhao, parametrizado pelo grupo (inmet, sismos, ...).
 * Fonte nova ganha tempo real sem precisar de canal novo.
 *
 * Privado, autorizado a qualquer usuario autenticado: o mesmo nivel que as rotas
 * /inmet e /sismos exigem, nem mais nem menos. O evento carrega apenas um
 * carimbo de tempo, mas dado operacional de Defesa Civil nao deve ser legivel
 * sem sessao.
 */
Broadcast::channel('medalhao.{grupo}', function ($user) {
    return $user !== null;
});
