import { useI18n as useVueI18n } from 'vue-i18n';
import { usePage } from '@inertiajs/vue3';
import { watch } from 'vue';
import i18n from '@/i18n';

export function useI18n() {
    const { t, locale } = useVueI18n();
    const page = usePage();

    // Sync locale with Inertia props
    watch(
        () => page.props.locale,
        (newLocale) => {
            if (newLocale && typeof newLocale === 'string' && newLocale !== locale.value) {
                i18n.global.locale.value = newLocale;
            }
        },
        { immediate: true }
    );

    const setLocale = (newLocale: string) => {
        i18n.global.locale.value = newLocale;
    };

    return {
        t,
        locale,
        setLocale,
    };
}

