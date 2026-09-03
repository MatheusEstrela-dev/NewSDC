<?php

use App\Modules\Pmda\Support\PerfilPmda;
use App\Modules\Shared\Events\RecursoAtualizado;
use App\Modules\Shared\Support\CanaisDeListagem;
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

/*
|--------------------------------------------------------------------------
| Canais das listagens de dominio
|--------------------------------------------------------------------------
|
| O canal so pode avisar quem ja poderia ver a pagina. Nenhum nome de permissao
| esta escrito aqui: a fonte e CanaisDeListagem, para que a divergencia com o
| `middleware('can:...')` da rota apareca como teste vermelho em vez de virar um
| canal mais permissivo que a listagem que ele atualiza.
|
| Os dois padroes sao disjuntos de proposito. O `{recurso}` do Laravel casa
| `([^.]+)`, entao `listagem.pmda-analises.3141` nao entra no padrao sem escopo --
| e cada callback recusa o recurso que pertence ao outro. Recurso escopado
| assinado sem escopo seria canal global sobre listagem recortada por municipio,
| que e exatamente o vazamento que o escopo existe para impedir.
|
*/

Broadcast::channel('listagem.{recurso}', function ($user, string $recurso) {
    if (CanaisDeListagem::exigeEscopo($recurso)) {
        return false;
    }

    $permissao = CanaisDeListagem::permissaoDe($recurso);

    // Null e negativa, nao "sem restricao": recurso fora da tabela nao autoriza.
    return $permissao !== null && $user->can($permissao);
});

Broadcast::channel('listagem.{recurso}.{escopo}', function ($user, string $recurso, string $escopo) {
    if (! CanaisDeListagem::exigeEscopo($recurso)) {
        return false;
    }

    $permissao = CanaisDeListagem::permissaoDe($recurso);

    if ($permissao === null || ! $user->can($permissao)) {
        return false;
    }

    // Id de municipio e sempre numerico, e `todos` nunca e: e essa diferenca
    // que separa "assino o meu municipio" de "assino o estado inteiro". Qualquer
    // outra coisa e recusada.
    $ehTodos = $escopo === RecursoAtualizado::ESCOPO_TODOS;

    if (! $ehTodos && ! ctype_digit($escopo)) {
        return false;
    }

    /*
     * A regra de escopo fica no modulo que a possui, e nao numa copia em
     * Shared: "quem e territorial" e nuance de dominio (so COMPDEC e; CEDEC,
     * REDEC e super-admin nao sao), e uma segunda implementacao dela divergiria
     * da listagem na primeira mudanca. O invariante que importa e que o canal
     * tenha o MESMO recorte de `PerfilPmda::aplicarEscopo()`.
     *
     * Recurso novo escopado entra aqui como novo braco do match. Sem braco,
     * recusa -- o default nao libera.
     */
    return match ($recurso) {
        'pmda-analises' => (function () use ($user, $escopo, $ehTodos): bool {
            $municipioDoUsuario = PerfilPmda::deUsuario($user)->municipioDoEscopo();

            // Null = le o estado inteiro (CEDEC, REDEC, super-admin). So esses
            // assinam o canal `todos`: para um COMPDEC ele seria justamente o
            // vazamento que o escopo existe para impedir.
            if ($ehTodos) {
                return $municipioDoUsuario === null;
            }

            return $municipioDoUsuario === null
                || $municipioDoUsuario === (int) $escopo;
        })(),
        default => false,
    };
});
