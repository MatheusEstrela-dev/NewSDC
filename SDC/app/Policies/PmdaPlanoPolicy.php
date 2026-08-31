<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Modules\Pmda\Models\PmdaPlano;
use App\Modules\Pmda\Support\PerfilPmda;

/**
 * Autorizacao do PMDA. Duas regras de negocio do legado, que o NewSDC nao
 * tinha, vivem aqui:
 *
 *  - quem cria PMDA e o municipio. O legado nao tinha seletor de municipio:
 *    create() gravava em Auth::user()->municipio_id. A issue #56 fecha o outro
 *    lado ("ocultar criacao de PMDA para CEDEC"), entao criar exige perfil
 *    COMPDEC com municipio vinculado.
 *  - COMPDEC so alcanca o PMDA do proprio municipio. Perfil estadual (CEDEC,
 *    REDEC, usuario sem orgao) nao tem recorte -- e ele que analisa o PMDA dos
 *    outros.
 *
 * A permissao (`pmda.planos.*`) continua respondendo "pode esta acao?"; a
 * policy acrescenta "neste territorio?".
 */
class PmdaPlanoPolicy extends BasePolicy
{
    public function create(User $user): bool
    {
        return $user->can('pmda.planos.create')
            && PerfilPmda::deUsuario($user)->podeCriarPmda();
    }

    public function view(User $user, PmdaPlano $plano): bool
    {
        return $user->can('pmda.planos.view') && $this->noEscopo($user, $plano);
    }

    public function update(User $user, PmdaPlano $plano): bool
    {
        return $user->can('pmda.planos.edit') && $this->noEscopo($user, $plano);
    }

    public function delete(User $user, PmdaPlano $plano): bool
    {
        return $user->can('pmda.planos.delete') && $this->noEscopo($user, $plano);
    }

    /**
     * So territorio, sem permissao.
     *
     * As rotas-filhas do plano (comunidades, pontos, anexos, ficha e equipe
     * COMPDEC) ja tem cada uma a sua propria permissao no middleware, e cada
     * uma responde por uma acao diferente. Trocar essas permissoes por
     * `pmda.planos.edit` mudaria silenciosamente o significado dos papeis ja
     * configurados; esta ability entra ao LADO delas e responde apenas "este
     * plano e do seu municipio?".
     *
     * Sem isso o furo so muda de lugar: bloquear /edit e deixar
     * POST /{plano}/comunidades aberto nao protege nada.
     */
    public function territorio(User $user, PmdaPlano $plano): bool
    {
        return $this->noEscopo($user, $plano);
    }

    /**
     * Territorio. Perfil estadual passa; COMPDEC so no proprio municipio.
     */
    private function noEscopo(User $user, PmdaPlano $plano): bool
    {
        $municipioDoUsuario = PerfilPmda::deUsuario($user)->municipioId();

        if ($municipioDoUsuario === null) {
            return true;
        }

        return $municipioDoUsuario === (int) $plano->municipio_id;
    }
}
