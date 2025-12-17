<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { toUrl, urlIsActive } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { show } from '@/routes/two-factor';
import { edit as editPassword } from '@/routes/user-password';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { User, Lock, Shield, Palette, Bell, Link as LinkIcon } from 'lucide-vue-next';
import { ref, onMounted } from 'vue';

const accountNavItems: NavItem[] = [
    {
        title: 'Perfil',
        href: editProfile(),
        icon: User,
    },
    {
        title: 'Senha',
        href: editPassword(),
        icon: Lock,
    },
    {
        title: 'Autenticação de Dois Fatores',
        href: show(),
        icon: Shield,
    },
    {
        title: 'Aparência',
        href: editAppearance(),
        icon: Palette,
    },
];

const platformNavItems: NavItem[] = [
    {
        title: 'Notificações',
        href: '/settings/notifications',
        icon: Bell,
    },
    {
        title: 'Integrações',
        href: '/settings/integrations',
        icon: LinkIcon,
    },
];

const currentPath = ref('');

onMounted(() => {
    if (typeof window !== 'undefined') {
        currentPath.value = window.location.pathname;
    }
});
</script>

<template>
    <div class="px-4 py-6">
        <Heading
            title="Configurações"
            description="Gerencie suas configurações pessoais e da plataforma"
        />

        <div class="flex flex-col lg:flex-row lg:space-x-12">
            <aside class="w-full max-w-xl lg:w-48">
                <nav class="flex flex-col space-y-6">
                    <!-- Account Settings -->
                    <div class="space-y-1">
                        <h3 class="mb-2 px-2 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                            Conta
                        </h3>
                        <Button
                            v-for="item in accountNavItems"
                            :key="toUrl(item.href)"
                            variant="ghost"
                            :class="[
                                'w-full justify-start',
                                { 'bg-muted': urlIsActive(item.href, currentPath) },
                            ]"
                            as-child
                        >
                            <Link :href="item.href">
                                <component :is="item.icon" class="h-4 w-4" />
                                {{ item.title }}
                            </Link>
                        </Button>
                    </div>

                    <Separator />

                    <!-- Platform Settings -->
                    <div class="space-y-1">
                        <h3 class="mb-2 px-2 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                            Plataforma
                        </h3>
                        <Button
                            v-for="item in platformNavItems"
                            :key="toUrl(item.href)"
                            variant="ghost"
                            :class="[
                                'w-full justify-start',
                                { 'bg-muted': urlIsActive(item.href, currentPath) },
                            ]"
                            as-child
                        >
                            <Link :href="item.href">
                                <component :is="item.icon" class="h-4 w-4" />
                                {{ item.title }}
                            </Link>
                        </Button>
                    </div>
                </nav>
            </aside>

            <Separator class="my-6 lg:hidden" />

            <div class="flex-1 md:max-w-2xl">
                <section class="max-w-xl space-y-12">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
