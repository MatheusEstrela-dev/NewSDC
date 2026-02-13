import { usePage } from '@inertiajs/vue3';

export function usePermissions() {
    const page = usePage();

    /**
     * Verifica se o usuário tem uma permissão específica
     * @param {string} permission - Slug da permissão (ex: 'users.view')
     * @returns {boolean}
     */
    const can = (permission) => {
        const user = page.props.auth?.user;

        // Se não tiver usuário, não tem permissão
        if (!user) return false;

        // Super Admin tem todas as permissões
        if (user.is_super_admin) return true;

        // Verificar array de permissões (diretas + via cargos)
        // O backend (HandleInertiaRequests) já deve entregar as permissões merged
        const userPermissions = user.permissions || [];

        return userPermissions.includes(permission);
    };

    /**
     * Verifica se o usuário tem qualquer uma das permissões listadas
     * @param {string[]} permissions - Array de slugs
     * @returns {boolean}
     */
    const canAny = (permissions) => {
        return permissions.some(p => can(p));
    };

    /**
     * Verifica se o usuário tem todas as permissões listadas
     * @param {string[]} permissions - Array de slugs
     * @returns {boolean}
     */
    const canAll = (permissions) => {
        return permissions.every(p => can(p));
    };

    return {
        can,
        canAny,
        canAll
    };
}
