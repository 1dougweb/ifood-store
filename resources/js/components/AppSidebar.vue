<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as restaurantsIndex } from '@/routes/restaurants';
import { index as ordersIndex } from '@/routes/orders';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import {
    LayoutGrid,
    Package,
    Store,
    Bell,
    Settings,
    HelpCircle,
    Link as LinkIcon,
} from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { usePermissions } from '@/composables/usePermissions';
import { computed } from 'vue';

const page = usePage();
const { hasPermission, permissions } = usePermissions();

const mainNavItems = computed<NavItem[]>(() => {
    // Acessar permissions.value para garantir reatividade
    const perms = permissions.value;
    
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: 'Pedidos',
            href: ordersIndex(),
            icon: Package,
        },
        {
            title: 'Restaurantes',
            href: restaurantsIndex(),
            icon: Store,
        },
    ];

    // Integrações apenas para quem tem permissão
    if (hasPermission('view-integrations')) {
        items.push({
            title: 'Integrações',
            href: '/integrations',
            icon: LinkIcon,
        });
    }

    // Notificações sempre visível
    items.push({
        title: 'Notificações',
        href: '/settings/notifications',
        icon: Bell,
    });

    return items;
});

const footerNavItems: NavItem[] = [
    {
        title: 'Configurações',
        href: '/settings/profile',
        icon: Settings,
    },
    {
        title: 'Ajuda',
        href: '#',
        icon: HelpCircle,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
