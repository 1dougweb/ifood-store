<script setup lang="ts">
import { computed } from 'vue';
import { usePermissions } from '@/composables/usePermissions';
import { useRoles } from '@/composables/useRoles';

interface Props {
    permission?: string | string[];
    role?: string | string[];
    any?: boolean; // If true, checks if user has ANY of the permissions/roles, otherwise ALL
}

const props = withDefaults(defineProps<Props>(), {
    permission: undefined,
    role: undefined,
    any: false,
});

const { hasPermission, hasAnyPermission, hasAllPermissions } = usePermissions();
const { hasRole, hasAnyRole, hasAllRoles } = useRoles();

const canAccess = computed(() => {
    if (props.permission) {
        const permissions = Array.isArray(props.permission) ? props.permission : [props.permission];
        return props.any ? hasAnyPermission(permissions) : hasAllPermissions(permissions);
    }

    if (props.role) {
        const roles = Array.isArray(props.role) ? props.role : [props.role];
        return props.any ? hasAnyRole(roles) : hasAllRoles(roles);
    }

    return false;
});
</script>

<template>
    <slot v-if="canAccess" />
</template>

