import { router } from '@inertiajs/vue3';
import { orders } from '@/routes/orders';
import { ref, onMounted, onUnmounted } from 'vue';

export function useOrders() {
    const isLoading = ref(false);
    const pollingInterval = ref<number | null>(null);

    const refreshOrders = (filters?: {
        restaurant_id?: number;
        status?: string;
        date_from?: string;
        date_to?: string;
    }) => {
        isLoading.value = true;
        router.reload({
            only: ['orders'],
            preserveState: true,
            preserveScroll: true,
            onFinish: () => {
                isLoading.value = false;
            },
        });
    };

    const startPolling = (
        interval: number = 30000,
        filters?: {
            restaurant_id?: number;
            status?: string;
            date_from?: string;
            date_to?: string;
        }
    ) => {
        if (pollingInterval.value) {
            clearInterval(pollingInterval.value);
        }

        pollingInterval.value = window.setInterval(() => {
            refreshOrders(filters);
        }, interval);
    };

    const stopPolling = () => {
        if (pollingInterval.value) {
            clearInterval(pollingInterval.value);
            pollingInterval.value = null;
        }
    };

    onUnmounted(() => {
        stopPolling();
    });

    return {
        isLoading,
        refreshOrders,
        startPolling,
        stopPolling,
    };
}

