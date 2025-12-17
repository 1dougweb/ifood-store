import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useRoles() {
    const page = usePage();

    const roles = computed(() => {
        const auth = page.props.auth as { user?: { roles?: string[] } } | null;
        return auth?.user?.roles || [];
    });

    const hasRole = (role: string): boolean => {
        return roles.value.includes(role);
    };

    const hasAnyRole = (roleList: string[]): boolean => {
        return roleList.some((role) => hasRole(role));
    };

    const hasAllRoles = (roleList: string[]): boolean => {
        return roleList.every((role) => hasRole(role));
    };

    const isAdmin = computed(() => hasRole('admin'));
    const isGestor = computed(() => hasRole('gestor'));
    const isCliente = computed(() => hasRole('cliente'));

    return {
        roles,
        hasRole,
        hasAnyRole,
        hasAllRoles,
        isAdmin,
        isGestor,
        isCliente,
    };
}

