<script setup lang="ts">
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { ToastContainer } from '@/components/ui/toast';
import type { BreadcrumbItemType } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { watch } from 'vue';
import { useToast } from '@/composables/useToast';
import i18n from '@/i18n';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const { success, error, warning, info } = useToast();

// Watch for flash messages
watch(
    () => page.props.flash,
    (flash) => {
        if (flash) {
            const flashObj = flash as {
                success?: string;
                error?: string;
                warning?: string;
                info?: string;
            };
            if (flashObj.success) success(flashObj.success);
            if (flashObj.error) error(flashObj.error);
            if (flashObj.warning) warning(flashObj.warning);
            if (flashObj.info) info(flashObj.info);
        }
    },
    { immediate: true }
);

// Watch for locale changes
watch(
    () => page.props.locale,
    (locale) => {
        if (locale && typeof locale === 'string' && locale !== i18n.global.locale.value) {
            i18n.global.locale.value = locale;
        }
    },
    { immediate: true }
);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <slot />
    </AppLayout>
    <ToastContainer />
</template>
