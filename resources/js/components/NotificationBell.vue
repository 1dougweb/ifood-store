<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { Bell } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Badge } from '@/components/ui/badge';
import { useI18n } from '@/composables/useI18n';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';

interface Notification {
    id: number;
    type: string;
    message: string;
    restaurant_name: string | null;
    order_id: number | null;
    created_at: string;
    read_at: string | null;
}

const { t } = useI18n();
const page = usePage();
const notifications = ref<Notification[]>([]);
const unreadCount = ref(0);
const isLoading = ref(false);
let pollInterval: number | null = null;

// Configure axios with CSRF token
onMounted(() => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
    }
    
    fetchNotifications();
    // Poll every 30 seconds
    pollInterval = window.setInterval(fetchNotifications, 30000);
});

const fetchNotifications = async () => {
    if (isLoading.value) return;

    try {
        isLoading.value = true;
        const response = await axios.get('/api/notifications');
        notifications.value = response.data.notifications || [];
        unreadCount.value = response.data.unread_count || 0;
    } catch (error) {
        console.error('Error fetching notifications:', error);
    } finally {
        isLoading.value = false;
    }
};

const markAsRead = async (id: number) => {
    try {
        await axios.post(`/api/notifications/${id}/read`);
        await fetchNotifications();
    } catch (error) {
        console.error('Error marking notification as read:', error);
    }
};

const markAllAsRead = async () => {
    try {
        await axios.post('/api/notifications/read-all');
        await fetchNotifications();
    } catch (error) {
        console.error('Error marking all as read:', error);
    }
};

const getNotificationIcon = (type: string) => {
    const icons = {
        new_order: '🆕',
        delayed_order: '⚠️',
        delivered_order: '✅',
        cancelled_order: '❌',
    };
    return icons[type as keyof typeof icons] || '📢';
};

onUnmounted(() => {
    if (pollInterval) {
        clearInterval(pollInterval);
    }
});
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon" class="relative h-9 w-9">
                <Bell class="h-5 w-5" />
                <Badge
                    v-if="unreadCount > 0"
                    variant="destructive"
                    class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full p-0 text-xs"
                >
                    {{ unreadCount > 9 ? '9+' : unreadCount }}
                </Badge>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-80">
            <div class="flex items-center justify-between p-2">
                <h3 class="font-semibold">{{ t('notification.title') }}</h3>
                <Button
                    v-if="unreadCount > 0"
                    variant="ghost"
                    size="sm"
                    @click="markAllAsRead"
                    class="h-7 text-xs"
                >
                    {{ t('notification.markAllAsRead') }}
                </Button>
            </div>
            <div class="max-h-96 overflow-y-auto">
                <div v-if="isLoading" class="p-4 text-center text-sm text-muted-foreground">
                    {{ t('common.loading') }}
                </div>
                <div
                    v-else-if="notifications.length === 0"
                    class="p-4 text-center text-sm text-muted-foreground"
                >
                    {{ t('notification.noNotifications') }}
                </div>
                <div v-else class="divide-y">
                    <button
                        v-for="notification in notifications"
                        :key="notification.id"
                        @click="markAsRead(notification.id)"
                        class="w-full p-3 text-left hover:bg-muted transition-colors"
                    >
                        <div class="flex items-start gap-2">
                            <span class="text-lg">{{ getNotificationIcon(notification.type) }}</span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium line-clamp-2">
                                    {{ notification.message }}
                                </p>
                                <p
                                    v-if="notification.restaurant_name"
                                    class="text-xs text-muted-foreground mt-1"
                                >
                                    {{ notification.restaurant_name }}
                                </p>
                                <p class="text-xs text-muted-foreground mt-1">
                                    {{ notification.created_at }}
                                </p>
                            </div>
                        </div>
                    </button>
                </div>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>

