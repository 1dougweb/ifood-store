import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

export function useMetrics() {
    const metrics = ref<any>(null);
    const isLoading = ref(false);

    const fetchMetrics = (restaurantId: number, days: number = 30) => {
        isLoading.value = true;
        router.get(
            '/dashboard',
            {
                restaurant_id: restaurantId,
                days,
            },
            {
                only: ['summaryMetrics', 'chartMetrics'],
                preserveState: true,
                onFinish: () => {
                    isLoading.value = false;
                },
            }
        );
    };

    const formatCurrency = (value: number) => {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL',
        }).format(value);
    };

    const formatNumber = (value: number) => {
        return new Intl.NumberFormat('pt-BR').format(value);
    };

    return {
        metrics,
        isLoading,
        fetchMetrics,
        formatCurrency,
        formatNumber,
    };
}

