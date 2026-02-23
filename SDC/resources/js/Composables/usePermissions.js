import { shallowRef, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

// Singleton module-level cache — permissões são estáveis entre navegações.
// Só atualiza quando o ID do usuário muda (login/logout).
const _permSet = shallowRef(null);
const _isSuperAdmin = shallowRef(false);
let _initialized = false;

function _syncFromPage(page) {
    const user = page.props?.auth?.user;
    _permSet.value = new Set(user?.permissions ?? []);
    _isSuperAdmin.value = user?.is_super_admin ?? false;
}

export function usePermissions() {
    const page = usePage();

    if (!_initialized) {
        _initialized = true;
        _syncFromPage(page);

        // Atualiza apenas quando o usuário muda (login/logout), NÃO em cada navegação
        watch(
            () => page.props?.auth?.user?.id,
            (newId, prevId) => {
                if (newId !== prevId) {
                    _syncFromPage(page);
                }
            }
        );
    }

    /**
     * Verifica se o usuário tem uma permissão específica — O(1) via Set
     */
    const can = (permission) => {
        if (_isSuperAdmin.value) return true;
        return _permSet.value?.has(permission) ?? false;
    };

    /**
     * Verifica se o usuário tem qualquer uma das permissões listadas
     */
    const canAny = (permissions) => permissions.some(p => can(p));

    /**
     * Verifica se o usuário tem todas as permissões listadas
     */
    const canAll = (permissions) => permissions.every(p => can(p));

    return { can, canAny, canAll };
}
