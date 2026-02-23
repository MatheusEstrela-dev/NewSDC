import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useHierarchy() {
    const page = usePage();

    const acl = computed(() => page.props.acl ?? {});
    const defaultLevel = computed(() => acl.value.default_level ?? 99);

    const currentUserLevel = computed(() => {
        const user = page.props.auth?.user;
        if (!user) return defaultLevel.value;

        if (user.hierarchy_level !== undefined) {
            return user.hierarchy_level;
        }

        if (!user.roles?.length) return defaultLevel.value;
        return Math.min(...user.roles.map(r => r.hierarchy_level ?? defaultLevel.value));
    });

    const isSuperAdmin = computed(() => currentUserLevel.value === 0);

    const canManageUser = (targetUser) => {
        if (isSuperAdmin.value) return true;
        if (!targetUser || !targetUser.roles) return true;

        const targetLevel = getLowestHierarchy(targetUser.roles);
        return currentUserLevel.value < targetLevel;
    };

    const canAssignRole = (role) => {
        if (isSuperAdmin.value) return true;
        if (!role) return false;

        const roleLevel = acl.value.levels?.[role.slug] ?? role.hierarchy_level ?? defaultLevel.value;
        return currentUserLevel.value < roleLevel;
    };

    const getAssignableRoles = (allRoles) => {
        if (!allRoles) return [];
        if (isSuperAdmin.value) return allRoles;

        return allRoles.filter(role => {
            const roleLevel = acl.value.levels?.[role.slug] ?? role.hierarchy_level ?? defaultLevel.value;
            return roleLevel > currentUserLevel.value;
        });
    };

    const getLowestHierarchy = (roles) => {
        if (!roles?.length) return defaultLevel.value;
        return Math.min(...roles.map(r => r.hierarchy_level ?? defaultLevel.value));
    };

    const isRoleDisabled = (role) => {
        if (isSuperAdmin.value) return false;
        if (isProtectedRole(role.slug)) return true;

        return !canAssignRole(role);
    };

    const isProtectedRole = (slug) => {
        return acl.value.protected_roles?.includes(slug) ?? false;
    };

    const isImmutablePermission = (permissionSlug) => {
        return acl.value.immutable_permissions?.includes(permissionSlug) ?? false;
    };

    const getPermissionModules = () => {
        return acl.value.modules ?? {};
    };

    const getLevelBySlug = (slug) => {
        return acl.value.levels?.[slug] ?? defaultLevel.value;
    };

    return {
        acl,
        currentUserLevel,
        isSuperAdmin,
        canManageUser,
        canAssignRole,
        getAssignableRoles,
        getLowestHierarchy,
        isRoleDisabled,
        isProtectedRole,
        isImmutablePermission,
        getPermissionModules,
        getLevelBySlug
    };
}
