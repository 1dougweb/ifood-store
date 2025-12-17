import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function usePermissions() {
    const page = usePage();

    const permissions = computed(() => {
        const auth = page.props.auth as { user?: { permissions?: string[] } } | null;
        return auth?.user?.permissions || [];
    });

    const hasPermission = (permission: string): boolean => {
        return permissions.value.includes(permission);
    };

    const hasAnyPermission = (permissionList: string[]): boolean => {
        return permissionList.some((permission) => hasPermission(permission));
    };

    const hasAllPermissions = (permissionList: string[]): boolean => {
        return permissionList.every((permission) => hasPermission(permission));
    };

    return {
        permissions,
        hasPermission,
        hasAnyPermission,
        hasAllPermissions,
    };
}

