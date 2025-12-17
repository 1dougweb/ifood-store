import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

export function useNotifications() {
    const notifications = ref<any[]>([]);
    const unreadCount = computed(() =>
        notifications.value.filter((n) => !n.read).length
    );

    const markAsRead = (id: number) => {
        const notification = notifications.value.find((n) => n.id === id);
        if (notification) {
            notification.read = true;
        }
    };

    const markAllAsRead = () => {
        notifications.value.forEach((n) => {
            n.read = true;
        });
    };

    const clearNotifications = () => {
        notifications.value = [];
    };

    return {
        notifications,
        unreadCount,
        markAsRead,
        markAllAsRead,
        clearNotifications,
    };
}

