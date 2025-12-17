<script setup lang="ts">
import { computed } from 'vue';
import { Globe } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useI18n } from '@/composables/useI18n';
import { router } from '@inertiajs/vue3';

const { locale, setLocale, t } = useI18n();

const languages = [
    { code: 'pt', name: 'Português', flag: '🇧🇷' },
    { code: 'en', name: 'English', flag: '🇺🇸' },
];

const currentLanguage = computed(() => {
    return languages.find((lang) => lang.code === locale.value) || languages[0];
});

const changeLanguage = async (langCode: string) => {
    if (langCode === locale.value) return;

    setLocale(langCode);

    // Update language on backend
    router.post(
        '/language',
        { language: langCode },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                // Language will be updated via Inertia props
            },
        }
    );
};
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon" class="h-9 w-9">
                <Globe class="h-5 w-5" />
                <span class="sr-only">{{ t('common.settings') }}</span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuItem
                v-for="lang in languages"
                :key="lang.code"
                @click="changeLanguage(lang.code)"
                :class="{ 'bg-muted': lang.code === locale }"
            >
                <span class="mr-2">{{ lang.flag }}</span>
                <span>{{ lang.name }}</span>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>

