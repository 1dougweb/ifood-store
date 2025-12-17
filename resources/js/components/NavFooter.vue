<script setup lang="ts">
import {
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { toUrl, urlIsActive } from '@/lib/utils';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';

interface Props {
    items: NavItem[];
    class?: string;
}

const props = defineProps<Props>();
const page = usePage();

const isExternalLink = (href: string | any): boolean => {
    if (typeof href === 'string') {
        return href.startsWith('http') || href.startsWith('//') || href === '#';
    }
    return false;
};
</script>

<template>
    <SidebarGroup
        :class="`group-data-[collapsible=icon]:p-0 ${props.class || ''}`"
    >
        <SidebarGroupLabel>Outros</SidebarGroupLabel>
        <SidebarGroupContent>
            <SidebarMenu>
                <SidebarMenuItem v-for="item in items" :key="item.title">
                    <SidebarMenuButton
                        v-if="isExternalLink(item.href)"
                        as-child
                        :tooltip="item.title"
                    >
                        <a
                            :href="toUrl(item.href)"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                        </a>
                    </SidebarMenuButton>
                    <SidebarMenuButton
                        v-else
                        as-child
                        :is-active="urlIsActive(item.href, page.url)"
                        :tooltip="item.title"
                    >
                        <Link :href="item.href">
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroupContent>
    </SidebarGroup>
</template>
