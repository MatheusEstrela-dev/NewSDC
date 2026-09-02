import { usePage } from '@inertiajs/vue3';

/**
 * Cache de modulo memoizado pela IDENTIDADE do array de permissoes.
 *
 * O array vem do `data-page` do Inertia: cada visita entrega um array NOVO, e
 * duas visitas seguidas sem mudanca de ACL entregam arrays diferentes com o
 * mesmo conteudo. Comparar a referencia basta para saber se vale remontar o
 * Set, e evita que as 27 telas que consomem este composable montem um Set de
 * ~230 strings cada uma por navegacao.
 */
let _origem = null;
let _permSet = new Set();

function _setDe(user) {
    const permissoes = user?.permissions ?? null;

    if (permissoes !== _origem) {
        _origem = permissoes;
        _permSet = new Set(permissoes ?? []);
    }

    return _permSet;
}

/**
 * Leitura das permissoes do usuario logado.
 *
 * A versao anterior guardava o Set num shallowRef de modulo protegido por um
 * flag `_initialized`, re-hidratado por um watch no `auth.user.id`. Dois
 * problemas, ambos silenciosos:
 *
 * 1. O id nao muda enquanto a pessoa segue logada. Permissao concedida no meio
 *    da sessao nunca chegava aqui -- os PermissionButton e ActionButton do app
 *    inteiro continuavam escondidos ate um F5. Mesmo bug que segurava o modulo
 *    PMDA na sidebar.
 * 2. O watch era registrado dentro do escopo do PRIMEIRO componente que
 *    chamasse o composable. Quando esse componente desmontava, o Vue derrubava
 *    o watcher junto e o singleton parava de atualizar de vez -- inclusive no
 *    login/logout que ele existia para cobrir.
 *
 * Agora `can()` le `page.props` a cada chamada: a dependencia reativa e
 * rastreada pelo computed de quem chamou, e o Set so e remontado quando o array
 * de origem troca.
 */
export function usePermissions() {
    const page = usePage();

    /** Verifica uma permissao especifica -- O(1) via Set. */
    const can = (permission) => {
        const user = page.props?.auth?.user;

        if (user?.is_super_admin) return true;

        return _setDe(user).has(permission);
    };

    /** Verifica se o usuario tem qualquer uma das permissoes listadas. */
    const canAny = (permissions) => permissions.some(p => can(p));

    /** Verifica se o usuario tem todas as permissoes listadas. */
    const canAll = (permissions) => permissions.every(p => can(p));

    return { can, canAny, canAll };
}
